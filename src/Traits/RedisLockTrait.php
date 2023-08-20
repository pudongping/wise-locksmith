<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-16 12:02
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Traits;

use Redis;
use RedisException;
use Pudongping\WiseLocksmith\Exception\WiseLocksmithException;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\LockAcquireException;
use Pudongping\WiseLocksmith\Exception\LockReleaseException;
use Pudongping\WiseLocksmith\Log;
use Pudongping\WiseLocksmith\Support\LuaScripts;
use Pudongping\WiseLocksmith\Support\RedisSupport;
use function Pudongping\WiseLocksmith\Support\s2ms;

trait RedisLockTrait
{

    /**
     * @param Redis $redis
     * @param string $key
     * @param string $value
     * @param float $expire
     * @return bool
     * @throws LockAcquireException
     */
    public function acquireLock($redis, string $key, string $value, float $expire): bool
    {
        try {
            return RedisSupport::distributedLock($redis, $key, $value, s2ms($expire));
        } catch (RedisException $redisException) {
            $msg = sprintf('Failed to acquire lock for key [%s] . Err msg : [%s]', $key, $redisException->getMessage());
            throw new LockAcquireException(ErrorCode::ERROR, $msg, $redisException);
        }
    }

    /**
     * @param Redis $redis
     * @param string $key
     * @param string $value
     * @return bool
     * @throws LockReleaseException
     */
    public function releaseLock($redis, string $key, string $value): bool
    {
        try {
            /**
             * link https://redis.io/commands/set
             */
            $result = RedisSupport::runLuaScript($redis, LuaScripts::release(), [$key, $value], 1);
        } catch (RedisException $redisException) {
            $msg = sprintf('Failed to release lock for key [%s] . Err msg : [%s]', $key, $redisException->getMessage());
            throw new LockReleaseException(ErrorCode::ERROR, $msg, $redisException);
        }

        if (! is_int($result)) {
            // 可能 lua 脚本执行失败
            return false;
        }

        if (0 === $result) {
            Log::getInstance()->logger()->notice("The lock key {$key}  don't release");
            return false;
        } elseif (1 === $result) {
            return true;
        }

        return false;
    }

    /**
     * @param Redis $redis
     * @param string $key
     * @return string
     */
    public function getLockToken($redis, string $key): string
    {
        try {
            $value = $redis->get($key);
        } catch (RedisException $redisException) {
            $msg = sprintf('Fetch the value of %s key has error. Err msg : [%s]', $key, $redisException->getMessage());
            throw new WiseLocksmithException(ErrorCode::ERROR, $msg, $redisException);
        }

        return (string)$value;
    }

    /**
     * 强制释放锁，不考虑任何情况
     *
     * @param Redis $redis
     * @param string $key
     * @return mixed
     */
    public function forceRelease($redis, string $key)
    {
        return $redis->del($key);
    }

}
