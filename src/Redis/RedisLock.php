<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-16 02:18
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Redis;

use Redis;
use Pudongping\WiseLocksmith\AbstractLock;
use Pudongping\WiseLocksmith\Support\RedisSupport;
use Pudongping\WiseLocksmith\Log;

class RedisLock extends AbstractLock
{

    /**
     * @var Redis
     */
    protected $redis;

    public function __construct($redis, string $key, int $timeoutMilliseconds, float $sleepSeconds = 0.25, ?string $token = null)
    {
        parent::__construct($key, $timeoutMilliseconds, $sleepSeconds, $token);

        $this->redis = $redis;
    }

    public function lock(): bool
    {
        return RedisSupport::distributedLock($this->redis, $this->key, $this->token, $this->timeoutMilliseconds);
    }

    public function unlock(): bool
    {
        /**
         * link https://redis.io/commands/set
         */
        $result = RedisSupport::runLuaScript($this->redis, LuaScripts::release(), [$this->key, $this->token], 1);
        if (! is_int($result)) {
            // 可能 lua 脚本执行失败
            return false;
        }

        if (0 === $result) {
            Log::getInstance()->logger()->notice("The lock key {$this->key}  don't release");
            return false;
        } elseif (1 === $result) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     * @throws \RedisException
     */
    public function getCurrentToken(): string
    {
        return $this->redis->get($this->key);
    }

    /**
     * @return false|int|Redis
     * @throws \RedisException
     */
    public function forceUnlock()
    {
        return $this->redis->del($this->key);
    }

}
