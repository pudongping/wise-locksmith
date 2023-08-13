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