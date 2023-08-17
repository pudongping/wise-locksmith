<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 11:36
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Support\Swoole;

use Throwable;
use ArrayObject;
use Swoole\Coroutine as SwooleCo;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\CoroutineException;
use Pudongping\WiseLocksmith\Log;

class SwooleEngine
{

    /**
     * 当前环境是否在协程环境下
     * see https://wiki.swoole.com/#/coroutine/coroutine?id=getcid
     *
     * @return bool
     */
    public static function inCoroutine(): bool
    {
        return self::id() > 0;
    }

    /**
     * 获取当前协程的 ID
     *
     * @return int
     */
    public static function id(): int
    {
        return SwooleCo::getCid();
    }

    /**
     * 获取当前协程的父 ID。在顶级协程中运行时返回 0。
     * see https://wiki.swoole.com/#/coroutine/coroutine?id=getpcid
     *
     * @param int|null $id
     * @return int
     * @throws CoroutineException
     */
    public static function pid(?int $id = null): int
    {
        if ($id) {
            $cid = SwooleCo::getPcid($id);
            if (is_bool($cid) && false === $cid) {
                // 已经有协程 ID，证明当前已经在协程环境下，但是返回值为 false，只能证明协程已经被销毁
                throw new CoroutineException(ErrorCode::ERR_CO_DESTROYED, sprintf('Coroutine #%d has been destroyed.', $id));
            }
        } else {
            $cid = SwooleCo::getPcid();
        }

        if (is_bool($cid) && false === $cid) {
            // 非协程环境
            throw new CoroutineException(ErrorCode::ERR_CO_NON_COROUTINE_ENV, 'Non-Coroutine environment don\'t has parent coroutine id.');
        }

        return max(0, $cid);
    }

    /**
     * 创建一个新的协程，并立即执行。
     * see https://wiki.swoole.com/#/coroutine/coroutine?id=create
     *
     * @param callable $callable
     * @return int
     */
    public static function create(callable $callable): int
    {
        $id = SwooleCo::create(static function () use ($callable) {
            try {
                $callable();
            } catch (Throwable $throwable) {
                static::printLog($throwable);
            }
        });

        if (is_null($id) || (is_bool($id) && false === $id)) {
            // 协程未被执行，创建协程失败 Coroutine was not be executed.
            return -1;
        }

        return $id;
    }

    /**
     * see https://wiki.swoole.com/#/coroutine/coroutine?id=defer
     *
     * @param callable $callable
     * @return void
     */
    public static function defer(callable $callable): void
    {
        SwooleCo::defer(static function () use ($callable) {
            try {
                $callable();
            } catch (Throwable $throwable) {
                static::printLog($throwable);
            }
        });
    }

    /**
     * 获取当前协程的上下文对象
     * see https://wiki.swoole.com/#/coroutine/coroutine?id=getcontext
     *
     * @param int|null $id
     * @return ArrayObject|null
     */
    public static function getContext(?int $id = null): ?ArrayObject
    {
        if ($id === null) {
            return SwooleCo::getContext();
        }

        return SwooleCo::getContext($id);
    }

    /**
     * 判断指定协程是否存在
     * see https://wiki.swoole.com/#/coroutine/coroutine?id=exists
     *
     * @param int $id
     * @return bool
     */
    public static function exists(int $id): bool
    {
        return SwooleCo::exists($id);
    }

    /**
     * 获取协程状态
     * https://wiki.swoole.com/#/coroutine/coroutine?id=stats
     *
     * @return array
     */
    public static function stats(): array
    {
        return SwooleCo::stats();
    }

    public static function sleep(float $seconds): void
    {
        usleep(intval($seconds * 1000 * 1000));
    }

    private static function printLog(Throwable $throwable): void
    {
        $log = Log::getInstance();
        $log->logger()->error($log->format($throwable));
    }

}
