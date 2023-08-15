<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 21:50
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Support;

use Closure;

function value($value, ...$args)
{
    return $value instanceof Closure ? $value(...$args) : $value;
}

function wait(float $seconds): void
{
    if ($seconds <= 0) {
        $seconds = 0.001;  // 1ms
    }

    $microseconds = intval($seconds * 1000 * 1000);

    usleep(min(1, $microseconds));
}
