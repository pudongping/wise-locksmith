<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-16 00:33
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith;

use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\LockAcquireException;
use Pudongping\WiseLocksmith\Exception\TimeoutException;
use function Pudongping\WiseLocksmith\Support\wait;

abstract class AbstractLock
{

    /**
     * 锁的名称
     *
     * @var string
     */
    protected $key;

    /**
     * 锁的值
     *
     * @var string
     */
    protected $token;

    /**
     * 锁的超时时间（自动过期时间），单位：毫秒
     *
     * @var int
     */
    protected $timeoutMilliseconds;

    /**
     * 在阻塞时重新尝试获取锁之前等待的秒数
     * 支持浮点数，eg：0.25 = 250ms
     *
     * @var float
     */
    protected $sleepSeconds;

    public function __construct(string $key, int $timeoutMilliseconds, float $sleepSeconds = 0.25, ?string $token = null)
    {
        $this->key = $key;
        $this->timeoutMilliseconds = $timeoutMilliseconds;
        $this->sleepSeconds = $sleepSeconds;

        if (! $token) {
            $token = $this->createToken();
        }
        $this->token = $token;

    }

    public function createToken(): string
    {
        return getmypid() . '_' . microtime() . '_' . uniqid('', true);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * 加锁
     *
     * @return bool
     */
    abstract public function lock(): bool;

    /**
     * 解锁
     *
     * @return bool
     */
    abstract public function unlock(): bool;

    /**
     * 当前锁对应的值
     *
     * @return string
     */
    abstract protected function getCurrentToken(): string;

    /**
     * 判断是否为当前进程自己加的锁
     *
     * @return bool
     */
    protected function isOwnedByCurrentProcess(): bool
    {
        return $this->getCurrentToken() === $this->getToken();
    }

    /**
     * 尝试抢占锁，如果抢占到锁，则执行回调函数
     *
     * @param callable $callback
     * @return null
     */
    public function acquire(callable $callback)
    {
        $locked = $this->lock();

        if ($locked && $this->isOwnedByCurrentProcess()) {
            try {
                return $callback();
            } finally {
                $this->unlock();
            }
        }

        return null;
    }

    /**
     * 尝试在给定的秒数内抢占锁，如果抢占到了锁，则执行回调函数
     *
     * @param callable $callback
     * @param int $seconds
     * @return callable
     * @throws LockAcquireException
     * @throws TimeoutException
     */
    public function block(callable $callback, int $seconds = 1)
    {
        $starting = microtime(true);

        while (! $this->lock()) {

            // 如果没有抢占到锁时
            wait($this->sleepSeconds);

            if (microtime(true) - $seconds >= $starting) {
                // 在给定的秒数内没有获取到锁
                throw new TimeoutException(ErrorCode::ERR_LOCK_TIMEOUT);
            }

        }

        // 抢占到了锁
        if ($this->isOwnedByCurrentProcess()) {
            throw new LockAcquireException(ErrorCode::ERR_LOCKED_NOT_BELONG_TO_MYSELF);
        }

        // 必须是自己加的锁才执行
        try {
            return $callback;
        } finally {
            $this->unlock();
        }

    }

}
