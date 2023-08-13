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
use Pudongping\WiseLocksmith\Swoole\SwooleEngine as Coroutine;
use function Swoole\Coroutine\run;
use PHPUnit\Framework\TestCase;
use Throwable;

class ChannelLockTest extends TestCase
{

    public function testLockAndUnlock()
    {
        $this->assertTrue(true);

        run(function () {
            Coroutine::create(function () {
                try {
                    $key = 'test_lock_key';
                    $result = ChannelLock::getInstance()->lock($key);
                    var_dump($result);
                    // 模拟阻塞代码
                    usleep(1000 * 1000);
                    $ret = ChannelLock::getInstance()->unlock($key);
                    var_dump($ret);
                } catch (Throwable $throwable) {
                    var_dump($throwable->getMessage());
                }

            });
        });

    }

    //  ./vendor/bin/phpunit --filter=testMultiCoroutineLock
    public function testMultiCoroutineLock()
    {
        $this->assertTrue(true);

        run(function () {

            $this->assertTrue(Coroutine::inCoroutine());

            for ($i = 1; $i < 100; $i++) {

                Coroutine::create(function () use ($i) {

                    $lock = ChannelLock::getInstance()->lock('hello');

                    // 模拟阻塞代码
                    usleep(200 * 1000);

                    echo sprintf('i=%d locked %s', $i, var_export($lock, true));

                    $release = ChannelLock::getInstance()->unlock('hello');

                    echo sprintf('  i=%d locked %s', $i, var_export($release, true)) . "\r\n";

                });

            }

        });

    }

    public function testDeferLock()
    {
        $this->assertTrue(true);

        Coroutine::create(function () {
            $lock = new ChannelLock();
            $key = 'test_defer_lock_key';

            $this->assertTrue($lock->deferLock($key));
            // Unlock will be deferred, so it should still be locked here
            $this->assertFalse($lock->lock($key)); // Lock already acquired by the same coroutine

            // Unlock should be triggered by defer
            Coroutine::defer(function () use ($lock, $key) {
                $this->assertTrue($lock->unlock($key));
            });
        });
    }

    public function testLockTimeout()
    {
        $this->assertTrue(true);

        Coroutine::create(function () {
            $lock = new ChannelLock();
            $key = 'test_lock_timeout_key';

            $this->assertTrue($lock->lock($key, 2)); // Lock for 2 seconds

            Coroutine::sleep(1); // Sleep for 1 second

            // Try to acquire lock again, should fail due to the timeout
            $this->assertFalse($lock->lock($key));
        });
    }

    public function testUnlockTimeout()
    {
        $this->assertTrue(true);

        Coroutine::create(function () {
            $lock = new ChannelLock();
            $key = 'test_unlock_timeout_key';

            $this->assertTrue($lock->lock($key)); // Acquire lock

            Coroutine::sleep(2); // Sleep for 2 seconds

            // Try to unlock after the timeout, should fail
            $this->assertFalse($lock->unlock($key));
        });
    }

}
