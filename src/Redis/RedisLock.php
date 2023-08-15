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

    public function lock()
    {
        return RedisSupport::distributedLock($this->redis, $this->key, $this->token, $this->timeoutMilliseconds);
    }

    public function unlock()
    {
        return (bool)RedisSupport::runLuaScript($this->redis, LuaScripts::release(), [$this->key, $this->token], 1);
    }

    public function getCurrentToken(): string
    {
        return $this->redis->get($this->key);
    }

    public function forceUnlock()
    {
        return $this->redis->del($this->key);
    }

}
