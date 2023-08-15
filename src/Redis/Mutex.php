<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 15:55
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Redis;

abstract class Mutex
{

    abstract public function synchronized(callable $call);

}
