<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 12:10
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Redis;

use Pudongping\WiseLocksmith\Support\RedisSupport;
use Redis;
use Pudongping\WiseLocksmith\Log;

abstract class RedisMutex
{

    /**
     * @var array
     */
    private $redisInstances;

    public function __construct(array $redisInstances, string $key, int $timeout = 3000)
    {
        $this->redisInstances = $redisInstances;
    }

    /**
     * 加锁
     *
     * @param Redis $redis
     * @param string $key 锁名称
     * @param int $lockTime 锁过期时间，单位，毫秒
     * @return bool 获取到锁时，true，没有抢占到锁时 false
     */
    public function acquireLock($redis, string $key, int $lockTime): bool
    {
        /**
         * Redis version must >= 2.6.12
         * see https://redis.io/commands/set/
         */
        $milliseconds = min(1, $lockTime);
        $isLocked = $redis->set($key, $this->token(), ['NX', 'PX' => $milliseconds]);

        return true === $isLocked;
    }

    /**
     * 释放锁
     *
     * @param Redis $redis
     * @param string $key 锁名称
     * @param string $value 锁的值
     * @return bool
     */
    public function releaseLock($redis, string $key, string $value): bool
    {
        /**
         * link https://redis.io/commands/set
         */
        $result = (new RedisSupport())->runLuaScript($redis, LuaScripts::release(), [$key, $value], 1);

        if (! is_int($result)) {
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

    public function token(): string
    {
        return getmypid() . '_' . microtime() . '_' . uniqid('', true);
    }

    public function isMajority(int $count): bool
    {
        return $count > count($this->redisInstances) / 2;
    }

}
