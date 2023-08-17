<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-18 01:03
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Test;

use PHPUnit\Framework\TestCase;
use Pudongping\WiseLocksmith\Support\Swoole\SwooleEngine;

abstract class AbstractTestCase extends TestCase
{

    public function mustSwoole()
    {
        $this->assertTrue(extension_loaded('swoole'), 'The swoole extension is missing from the current runtime.');
    }

    public function mustRunInSwoole()
    {
        $this->assertTrue(SwooleEngine::inCoroutine(), 'Non-Coroutine environment.');
    }

}
