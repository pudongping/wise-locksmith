<?php
/**
 * redis 单实例
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-16 02:18
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Lock\Redis;

use Redis;
use Pudongping\WiseLocksmith\Contract\LoopInterface;
use Pudongping\WiseLocksmith\Mutex\SpinlockMutex;
use Pudongping\WiseLocksmith\Traits\RedisLockTrait;
use Pudongping\WiseLocksmith\Exception\WiseLocksmithException;

class RedisLock extends SpinlockMutex
{

    use RedisLockTrait;

    /**
     * @var Redis
     */
    protected $redis;

    public function __construct(
        $redis,
        string $key,
        float $timeoutSeconds,
        ?string $token = null,
        ?LoopInterface $loop = null
    ) {
        parent::__construct($key, $timeoutSeconds, $token, $loop);

        $this->redis = $redis;
    }

    public function lock(string $key, float $timeoutSeconds, string $token): bool
    {
        $locked = $this->acquireLock($this->redis, $key, $token, $timeoutSeconds);

        // 只有自己抢占到了自己设定的锁，才能算真正的抢占到了锁
        $owner = $this->isOwnedByCurrentProcess();

        return $locked && $owner;
    }

    public function unlock(string $key, string $token): bool
    {
        return $this->releaseLock($this->redis, $this->key, $this->token);
    }

    /**
     * 判断是否为当前进程自己加的锁
     *
     * @return bool
     * @throws WiseLocksmithException
     */
    public function isOwnedByCurrentProcess(): bool
    {
        return $this->getLockToken($this->redis, $this->key) === $this->token;
    }

}
