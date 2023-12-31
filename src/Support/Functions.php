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

if (! function_exists('value')) {
    /**
     * @param $value
     * @param ...$args
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('wait')) {
    /**
     * @param float $seconds
     * @return void
     */
    function wait(float $seconds): void
    {
        if ($seconds <= 0) {
            $seconds = 1e-3;  // 0.001s = 1ms
        }

        $microseconds = intval($seconds * 1e6);

        usleep(min(1, $microseconds));
    }
}

if (! function_exists('s2ms')) {
    /**
     * 秒转化为毫秒
     *
     * @param float $seconds 秒
     * @return int 毫秒
     */
    function s2ms(float $seconds): int
    {
        return intval($seconds * 1e3);
    }
}

if (! function_exists('command_execute_time')) {
    /**
     * 执行命令所需要的毫秒数
     *
     * @param float $start 开始执行脚本时的微秒数
     * @param bool $ms true 返回毫秒数，false 返回秒数
     * @return float
     */
    function command_execute_time(float $start, bool $ms = true): float
    {
        $cost = microtime(true) - $start;

        if ($ms) {
            return round($cost * 1e3, 4);
        }

        return round($cost, 4);
    }
}

if (! function_exists('create_token')) {
    /**
     * @return string
     */
    function create_token(): string
    {
        return getmypid() . '_' . microtime() . '_' . uniqid('', true);
    }
}

