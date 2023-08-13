<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 23:52
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Test\Swoole;

use Pudongping\WiseLocksmith\Swoole\Context;
use Pudongping\WiseLocksmith\Swoole\SwooleEngine as Coroutine;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function testSetAndGetInCoroutine()
    {
        Coroutine::create(function () {
            Context::set('key', 'value');
            $this->assertEquals('value', Context::get('key'));
        });
    }

    public function testSetAndGetOutsideCoroutine()
    {
        Context::set('key', 'value');
        $this->assertEquals('value', Context::get('key'));
    }

    public function testGetWithDefault()
    {
        $this->assertNull(Context::get('nonexistent_key'));
        $this->assertEquals('default_value', Context::get('nonexistent_key', 'default_value'));
    }

    public function testHasInCoroutine()
    {
        Coroutine::create(function () {
            Context::set('key', 'value');
            $this->assertTrue(Context::has('key'));
            $this->assertFalse(Context::has('nonexistent_key'));
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
        Coroutine::create(function () {
            Context::set('key', 'value');
            Context::destroy('key');
            $this->assertFalse(Context::has('key'));
        });
    }

    public function testDestroyOutsideCoroutine()
    {
        Context::set('key', 'value');
        Context::destroy('key');
        $this->assertFalse(Context::has('key'));
    }

}
