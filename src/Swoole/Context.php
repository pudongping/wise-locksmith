<?php
/**
 *
 * see https://wiki.swoole.com/#/coroutine?id=%e5%85%a8%e5%b1%80%e5%8f%98%e9%87%8f
 * https://wiki.swoole.com/#/coroutine/coroutine?id=getcontext
 * Thanks for https://github.com/hyperf/context
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 14:35
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Swoole;

use Pudongping\WiseLocksmith\Swoole\SwooleEngine as Coroutine;
use function Pudongping\WiseLocksmith\Support\value;

class Context
{

    /**
     * 非协程环境下的上下文
     *
     * @var array
     */
    protected static $nonCoContext = [];

    public static function set(string $key, $value, ?int $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            Coroutine::getContext($coroutineId)[$key] = $value;
        } else {
            self::$nonCoContext[$key] = $value;
        }

        return $value;
    }

    public static function get(string $key, $default = null, ?int $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            return Coroutine::getContext($coroutineId)[$key] ?? $default;
        }

        return self::$nonCoContext[$key] ?? $default;
    }

    public static function has(string $key, ?int $coroutineId = null): bool
    {
        if (Coroutine::inCoroutine()) {
            return isset(Coroutine::getContext($coroutineId)[$key]);
        }

        return isset(self::$nonCoContext[$key]);
    }

    public static function destroy(string $key, ?int $coroutineId = null): void
    {
        if (Coroutine::inCoroutine()) {
            unset(Coroutine::getContext($coroutineId)[$key]);
        }

        unset(self::$nonCoContext[$key]);
    }

    /**
     * 将上下文从指定协程复制到当前协程。
     * 此方法将删除当前协程中的原始值。
     *
     * @param int $fromCoroutineId
     * @param array $keys
     * @return void
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void
    {
        if (! Coroutine::inCoroutine()) return;

        $from = Coroutine::getContext($fromCoroutineId);

        if (is_null($from)) return;

        $current = Coroutine::getContext();

        if ($keys) {
            $map = array_intersect_key($from->getArrayCopy(), array_flip($keys));
        } else {
            $map = $from->getArrayCopy();
        }

        $current->exchangeArray($map);
    }

    /**
     * 检索值并通过闭包覆盖它。
     *
     * @param string $key
     * @param callable $closure
     * @param int|null $coroutineId
     * @return mixed
     */
    public static function override(string $key, callable $closure, ?int $coroutineId = null)
    {
        $value = null;

        if (self::has($key, $coroutineId)) {
            $value = self::get($key, null, $coroutineId);
        }

        $value = $closure($value);

        self::set($key, $value, $coroutineId);

        return $value;
    }

    /**
     * 存在则检索并返回，不存在则存储值
     *
     * @param string $key
     * @param $value
     * @param int|null $coroutineId
     * @return false|mixed|null
     */
    public static function getOrSet(string $key, $value, ?int $coroutineId = null)
    {
        if (self::has($key, $coroutineId)) {
            return self::get($key, null, $coroutineId);
        }

        return self::set($key, value($value), $coroutineId);
    }

    public static function getContainer(?int $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            return Coroutine::getContext($coroutineId);
        }

        return self::$nonCoContext;
    }

    public static function clear(?int $coroutineId = null): void
    {
        foreach (self::getContainer($coroutineId) as $key => $value) {
            self::destroy($key, $coroutineId);
        }
    }

}