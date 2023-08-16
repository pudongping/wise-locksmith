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

class RedisLock extends AbstractLock
{

    /**
     * @var Redis
     */
    protected $redis;

    public function __construct($redis, string $key, float $timeoutSeconds, float $sleepSeconds = 0.25, ?string $token = null)
    {
        parent::__construct($key, $timeoutSeconds, $sleepSeconds, $token);

        $this->redis = $redis;
    }

    public function lock(): bool
    {
        return $this->acquireLock($this->redis, $this->key, $this->token, $this->timeoutSeconds);
    }

    public function unlock(): bool
    {
        return $this->releaseLock($this->redis, $this->key, $this->token);
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
