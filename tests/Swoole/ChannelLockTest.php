<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 23:20
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Test\Swoole;

use Pudongping\WiseLocksmith\Swoole\ChannelLock;
use Swoole\Coroutine;
use Pudongping\WiseLocksmith\Swoole\SwooleEngine;
use PHPUnit\Framework\TestCase;
use Throwable;
use function Swoole\Coroutine\run;

class ChannelLockTest extends TestCase
{

    public function testLockAndUnlock()
    {
        run(function () {
            Coroutine::create(function () {

                $start = microtime(true);
                $key = 'test_lock_key';
                $result = ChannelLock::getInstance()->lock($key);
                $this->assertTrue($result);
                // 模拟阻塞代码
                usleep(1000 * 1000);
                $ret = ChannelLock::getInstance()->unlock($key);
                $this->assertTrue($ret);
                $end = microtime(true);

                $this->assertGreaterThan(1, $end - $start);

            });
        });

    }

    //  ./vendor/bin/phpunit --filter=testMultiCoroutineLock
    public function testMultiCoroutineLock()
    {
        run(function () {

            $this->assertTrue(SwooleEngine::inCoroutine());

            $wg = new Coroutine\WaitGroup();
            $startWrap = microtime(true);
            $results = [];

            for ($i = 1; $i <= 100; $i++) {
                $wg->add(1);

                Coroutine::create(function () use ($i, $wg, &$results) {

                    $start = microtime(true);
                    $lock = ChannelLock::getInstance()->lock('hello');
                    $this->assertTrue($lock);

                    // 模拟阻塞代码 0.2s
                    Coroutine::sleep(0.2);

                    $a = sprintf('i=%d locked %s', $i, var_export($lock, true));

                    $release = ChannelLock::getInstance()->unlock('hello');
                    $this->assertTrue($release);
                    $end = microtime(true);

                    $b = sprintf('  i=%d locked %s %s-%s=%s', $i, var_export($release, true), $end, $start, $end - $start);
                    echo $a . $b . "\r\n";

                    $results[] = $a . $b;
                    $wg->done();
                });

            }

            // 100 个协程将被阻塞等待执行
            $this->assertEquals(100, $wg->count());

            // 阻塞代码，等待所有的协程执行完毕
            $wg->wait();

            $endWrap = microtime(true);
            // 0.2 * 100 = 20 等所有的协程执行完毕应该会超过 20s
            $this->assertGreaterThan(20, $endWrap - $startWrap);
            // 估计会在 25s 内执行完毕
            $this->assertLessThan(25, $endWrap - $startWrap);

            // 代表已经收集到了 100 个结果集，也就是 100 个协程都被执行到了，证实了代码逻辑的完整性
            // 如果打印出 $results 的话，可以看出 $i 是有序的，则证实了串行化执行
            $this->assertCount(100, $results);
            // 所有的协程都已经执行完毕
            $this->assertEquals(0, $wg->count());

        });

    }

    // 实现的效果和 testMultiCoroutineLock 一样
    public function testDeferLock()
    {
        run(function () {

            $this->assertTrue(SwooleEngine::inCoroutine());

            $wg = new Coroutine\WaitGroup();
            $startWrap = microtime(true);
            $results = [];

            for ($i = 1; $i <= 100; $i++) {
                $wg->add(1);

                Coroutine::create(function () use ($i, $wg, &$results) {

                    $start = microtime(true);
                    $lock = ChannelLock::getInstance()->deferLock('test_defer_lock_key');
                    $this->assertTrue($lock);

                    // 模拟阻塞代码 0.2s
                    Coroutine::sleep(0.2);

                    $a = sprintf('i=%d locked %s', $i, var_export($lock, true));

                    $end = microtime(true);

                    $b = sprintf('  i=%d locked %s-%s=%s', $i, $end, $start, $end - $start);
                    echo $a . $b . "\r\n";

                    $results[] = $a . $b;
                    $wg->done();
                });

            }

            // 100 个协程将被阻塞等待执行
            $this->assertEquals(100, $wg->count());

            // 阻塞代码，等待所有的协程执行完毕
            $wg->wait();

            $endWrap = microtime(true);
            // 0.2 * 100 = 20 等所有的协程执行完毕应该会超过 20s
            $this->assertGreaterThan(20, $endWrap - $startWrap);
            // 估计会在 25s 内执行完毕
            $this->assertLessThan(25, $endWrap - $startWrap);

            // 代表已经收集到了 100 个结果集，也就是 100 个协程都被执行到了，证实了代码逻辑的完整性
            // 如果打印出 $results 的话，可以看出 $i 是有序的，则证实了串行化执行
            $this->assertCount(100, $results);
            // 所有的协程都已经执行完毕
            $this->assertEquals(0, $wg->count());


        });

    }

    public function testLockTimeout1()
    {
        run(function () {

            for ($i = 1; $i <= 100; $i++) {

                Coroutine::create(function () use ($i) {
                    $key = 'test_lock_timeout_key1';

                    // 只会有第一个协程会抢到锁，因为通道容量只有 1 并且没有任何消费者进行消费通道，因此
                    // 后面进来的协程会因为通道 pop 超时而直接返回 false（其实在这里根本就没有使用 pop 自然必然会超时）
                    $lock1 = ChannelLock::getInstance()->lock($key, 1.5);
                    if ($i == 1) {
                        // 只有第一个会获取到锁，其他都会获取不到
                        $this->assertTrue($lock1);
                    } else {
                        $this->assertFalse($lock1);
                    }

                    // 不管这里阻塞时间是小于 1.5s 还是大于等于 1.5s 都将只会有第一个协程获取到锁，其他的协程都将失败，因为没有通道消费者
                    Coroutine::sleep(0.01);
                    // Coroutine::sleep(2);

                    echo sprintf('i=%d --- lock=%s', $i, var_export($lock1, true)) . "\r\n";

                });

            }

        });

    }

    public function testLockTimeout2()
    {
        run(function () {

            for ($i = 1; $i <= 100; $i++) {

                Coroutine::create(function () use ($i) {
                    $key = 'test_lock_timeout_key2';

                    // 在超时时间内，如果没有任何消费者消费数据，那么将会发生超时，
                    // 也就意味着在 1.2s 内，解锁操作被执行多少次，也就会有多少个协程能够抢得到锁
                    // 按照如下代码来看 1.2 / 0.5 <= 3
                    $lock1 = ChannelLock::getInstance()->lock($key, 1.2);
                    // 只需要判断第 4 个协程之后的协程都将获取不到锁即可
                    if ($i >= 4) {
                        $this->assertFalse($lock1);
                    }

                    // 0.5 < 1.2 那么将会有 1.2 / 0.5 <= 3 个协程抢得到锁
                    // Coroutine::sleep(0.5);
                    // 1.3 > 1.2 那么将只会有第一个协程抢得到锁
                    Coroutine::sleep(1.3);
                    // 1.2 = 1.2 那么将会有两个协程抢得到锁
                    // Coroutine::sleep(1.2);

                    $lock2 = ChannelLock::getInstance()->unlock($key);
                    $this->assertTrue($lock2);

                    echo sprintf('i=%d --- lock1=%s lock2=%s', $i, var_export($lock1, true), var_export($lock2, true)) . "\r\n";

                });

            }

        });
    }

    //  ./vendor/bin/phpunit --filter ChannelLockTest::testUnlockTimeout1
    public function testUnlockTimeout1()
    {
        run(function () {

            for ($i = 1; $i <= 100; $i++) {

                Coroutine::create(function () use ($i) {

                    $key = 'test_unlock_timeout_key1';
                    $lock1 = ChannelLock::getInstance()->lock($key, 3.5);
                    // 3.5 / 1 <= 4
                    if ($i >= 5) {
                        $this->assertFalse($lock1);
                    }

                    Coroutine::sleep(1);

                    $lock2 = ChannelLock::getInstance()->unlock($key, 8);
                    $this->assertTrue($lock2);

                    echo sprintf('i=%d --- lock1=%s lock2=%s', $i, var_export($lock1, true), var_export($lock2, true)) . "\r\n";

                });

            }

        });

    }

}
