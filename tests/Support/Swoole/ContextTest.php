<?php
/**
 * ./vendor/bin/phpunit --filter ContextTest
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 23:52
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Test\Support\Swoole;

use Pudongping\WiseLocksmith\Support\Swoole\Context;

// 还是得使用 use Swoole\Coroutine; 否则错误信息被我封装的 SwooleEngine 给捕获了
// use Pudongping\WiseLocksmith\Swoole\SwooleEngine as Coroutine;
use Swoole\Coroutine;
use stdClass;
use Pudongping\WiseLocksmith\Test\AbstractTestCase;
use function Swoole\Coroutine\parallel;
use function Swoole\Coroutine\run;

class ContextTest extends AbstractTestCase
{

    //  ./vendor/bin/phpunit --filter ContextTest::testSetAndGetInCoroutine
    // ./vendor/bin/phpunit --filter=testSetAndGetInCoroutine
    public function testSetAndGetInCoroutine()
    {
        $this->mustSwoole();

        run(function () {

            $id = Coroutine::create(function () {
                Context::set('key', 'value');
                $this->assertEquals('value', Context::get('key'));
            });

            // 协程被执行
            $this->assertTrue($id > 0);
        });
    }

    // ./vendor/bin/phpunit --filter=testSetAndGetOutsideCoroutine
    public function testSetAndGetOutsideCoroutine()
    {
        Context::set('key', 'value');
        $this->assertEquals('value', Context::get('key'));
    }

    // ./vendor/bin/phpunit --filter=testGetWithDefault
    public function testGetWithDefault()
    {
        $this->assertNull(Context::get('nonexistent_key'));
        $this->assertEquals('default_value', Context::get('nonexistent_key', 'default_value'));
    }

    public function testHasInCoroutine()
    {
        $this->mustSwoole();

        run(function () {
            $id = Coroutine::create(function () {
                Context::set('key', 'value');
                $this->assertTrue(Context::has('key'));
                $this->assertFalse(Context::has('nonexistent_key'));
            });

            // 协程被执行
            $this->assertTrue($id > 0);
        });
    }

    public function testHasOutsideCoroutine()
    {
        Context::set('key', 'value');
        $this->assertTrue(Context::has('key'));
        $this->assertFalse(Context::has('nonexistent_key'));
    }

    public function testDestroyInCoroutine()
    {
        run(function () {
            $id = Coroutine::create(function () {
                Context::set('key', 'value');
                Context::destroy('key');
                $this->assertFalse(Context::has('key'));
            });

            // 协程被执行
            $this->assertTrue($id > 0);
        });
    }

    public function testDestroyOutsideCoroutine()
    {
        Context::set('key', 'value');
        Context::destroy('key');
        $this->assertFalse(Context::has('key'));
    }

    public function testOverride()
    {
        run(function () {

            Context::set('override.id', 1);

            $this->assertSame(2, Context::override('override.id', function ($id) {
                return $id + 1;
            }));

            $this->assertSame(2, Context::get('override.id'));

        });
    }

    public function testGetOrSet()
    {
        run(function () {

            Context::set('test.store.id', null);
            $this->assertSame(1, Context::getOrSet('test.store.id', function () {
                return 1;
            }));
            $this->assertSame(1, Context::getOrSet('test.store.id', function () {
                return 2;
            }));

            Context::set('test.store.id', null);
            $this->assertSame(1, Context::getOrSet('test.store.id', 1));

        });
    }

    public function testCopy()
    {
        run(function () {

            Context::set('test.store.id', $uid = uniqid());
            $id = Coroutine::getCid();
            parallel(1, function () use ($id, $uid) {
                Context::copy($id, ['test.store.id']);
                $this->assertSame($uid, Context::get('test.store.id'));
            });

        });
    }

    // ./vendor/bin/phpunit --filter ContextTest::testCopyAfterSet
    public function testCopyAfterSet()
    {
        run(function () {

            Context::set('test.store.id', $uid = uniqid());
            $id = Coroutine::getCid();
            parallel(1, function () use ($id, $uid) {
                $cid = Coroutine::getCid();

                // 证明确实发生了协程切换
                $this->assertGreaterThan($id, $cid);
                $this->assertEquals($id, Coroutine::getPcid($cid));

                Context::set('test.store.name', 'Hyperf');
                Context::copy($id, ['test.store.id']);
                $this->assertSame($uid, Context::get('test.store.id'));

                // Context::copy 会删掉原始值
                $this->assertNull(Context::get('test.store.name'));
            });

        });
    }

    public function testContextChangeAfterCopy()
    {
        run(function () {

            $obj = new stdClass();
            $obj->id = $uid = uniqid();

            Context::set('test.store.id', $obj);
            Context::set('test.store.useless.id', 1);
            $id = Coroutine::getCid();
            $tid = uniqid();
            parallel(1, function () use ($id, $uid, $tid) {
                Context::copy($id, ['test.store.id']);
                $obj = Context::get('test.store.id');
                $this->assertSame($uid, $obj->id);
                $obj->id = $tid;
                $this->assertFalse(Context::has('test.store.useless.id'));
            });

            $this->assertSame($tid, Context::get('test.store.id')->id);

        });

    }

    public function testContextFromNull()
    {
        run(function () {

            $res = Context::get('id', $default = 'Hello World!', -1);
            $this->assertSame($default, $res);

            $res = Context::get('id', null, -1);
            $this->assertSame(null, $res);

            $this->assertFalse(Context::has('id', -1));

            Context::copy(-1);

            $start = microtime(true);
            parallel(1, function () {
                Context::set('id', $id = uniqid());
                Context::copy(-1, ['id']);
                $this->assertSame($id, Context::get('id'));
            });
            $end = microtime(true);

            $this->assertGreaterThan(0, $end - $start);

        });

    }

    public function testContextDestroy()
    {
        run(function () {

            Context::set($id = uniqid(), $value = uniqid());

            $this->assertSame($value, Context::get($id));
            Context::destroy($id);
            $this->assertNull(Context::get($id));

        });

    }

    // ./vendor/bin/phpunit --filter ContextTest::testContextClear
    public function testContextClear()
    {
        run(function () {

            $this->assertEmpty(Context::getContainer());

            Context::set($id1 = 'alex1', $value1 = uniqid());
            Context::set($id2 = 'alex2', $value2 = uniqid());

            $this->assertCount(2, Context::getContainer());
            $this->assertSame($value1, Context::get($id1));
            $this->assertSame($value2, Context::get($id2));

            Context::clear();

            $this->assertEmpty(Context::getContainer());

            $this->assertFalse(Context::has($id1));
            $this->assertFalse(Context::has($id2));

        });
    }

}
