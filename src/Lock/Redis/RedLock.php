<?php
/**
 * redis 分布式集群
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 19:04
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Lock\Redis;

use Redis;
use RedisCluster;
use Throwable;
use Pudongping\WiseLocksmith\Contract\LoopInterface;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\LockAcquireException;
use Pudongping\WiseLocksmith\Log;
use Pudongping\WiseLocksmith\Mutex\SpinlockMutex;
use Pudongping\WiseLocksmith\Traits\RedisLockTrait;

class RedLock extends SpinlockMutex
{

    use RedisLockTrait;

    /**
     * @var array<Redis|RedisCluster>
     */
    private $redisInstances;

    public function __construct(
        array          $redisInstances,
        string         $key,
        float          $timeoutSeconds,
        ?string        $token = null,
        ?LoopInterface $loop = null
    ) {
        parent::__construct($key, $timeoutSeconds, $token, $loop);

        $this->redisInstances = $redisInstances;
    }

    public function lock(string $key, float $timeoutSeconds, string $token): bool
    {
        $time = microtime(true);

        $acquired = 0;  // 记录抢占到锁的个数
        $errored = 0;  // 记录失败个数
        $exception = null;

        // 尝试对每个实例加锁
        foreach ($this->redisInstances as $index => $redisInstance) {
            try {
                if ($this->acquireLock($redisInstance, $key, $token, $timeoutSeconds)) {
                    $acquired++;
                }
            } catch (Throwable $exception) {
                $context = [
                    'key' => $key,
                    'index' => $index,
                    'token' => $token,
                    'exception' => $exception
                ];
                Log::getInstance()->logger()->warning('Could not set {key} = {token} at server #{index}.', $context);

                $errored++;
            }
        }

        // 计算获取锁所花的时间
        $elapsedTime = microtime(true) - $time;

        // 当且仅当客户端能够在大多数实例中获取锁，且获取锁的总时间小于「锁有效期」时，则认为锁已被获取
        $isAcquired = $this->isMajority($acquired) && $elapsedTime <= $timeoutSeconds;

        if ($isAcquired) {
            return true;
        }

        // 如果客户端由于某种原因未能获得锁（它无法锁定 (N/2)+1 个实例或有效时间为负数），
        // 它将尝试解锁所有实例（即使是被它认为不能锁定的实例）
        $this->unlock($key, $token);

        // 如果大多数的服务器加锁失败
        if ($this->isMajority($errored)) {
            assert(! is_null($exception));
            throw new LockAcquireException(
                ErrorCode::ERROR,
                "It's not possible to acquire a lock because at least half of the Redis server are not available.",
                $exception
            );
        }

        return false;
    }

    public function unlock(string $key, string $token): bool
    {
        $released = 0;  // 记录释放锁的个数

        foreach ($this->redisInstances as $index => $redisInstance) {
            try {
                if ($this->releaseLock($redisInstance, $key, $token)) {
                    $released++;
                }
            } catch (\Throwable $exception) {
                $context = [
                    'key' => $key,
                    'index' => $index,
                    'token' => $token,
                    'exception' => $exception
                ];

                Log::getInstance()->logger()->warning('Could not unset {key} = {token} at server #{index}.', $context);
            }
        }

        return $this->isMajority($released);
    }

    /**
     * 当前计数是否达到所有服务器的半数以上
     *
     * @param int $count
     * @return bool
     */
    public function isMajority(int $count): bool
    {
        return $count > count($this->redisInstances) / 2;
    }

}
