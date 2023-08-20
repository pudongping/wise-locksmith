<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 15:12
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith;

use Redis;
use RedisCluster;
use Psr\Log\LoggerInterface;
use Pudongping\WiseLocksmith\Lock\Redis\RedisLock;
use Pudongping\WiseLocksmith\Lock\Redis\RedLock;
use Pudongping\WiseLocksmith\Lock\File\Flock;
use Pudongping\WiseLocksmith\Lock\Swoole\ChannelLock;
use Pudongping\WiseLocksmith\Contract\LoopInterface;

class Locker
{

    public function setLogger(LoggerInterface $logger): Locker
    {
        Log::getInstance()->setLogger($logger);

        return $this;
    }

    /**
     * @param resource $fileHandle 文件资源
     * @param callable $businessLogic 业务逻辑代码
     * @param float $timeoutSeconds 超时时间，单位：秒（支持浮点型，如 1.5 表示 1s+500ms，-1 表示永不超时）
     * @param LoopInterface|null $loop 循环器
     * @return mixed
     * @throws Exception\InvalidArgumentException
     * @throws Exception\MutexException
     * @throws \Throwable
     */
    public function flock($fileHandle, callable $businessLogic, float $timeoutSeconds = Flock::INFINITE_TIMEOUT, ?LoopInterface $loop = null)
    {
        return (new Flock($fileHandle, $timeoutSeconds, $loop))->synchronized($businessLogic);
    }

    /**
     * @param string $key 锁的名称
     * @param callable $businessLogic 业务逻辑代码
     * @param float $timeoutSeconds 超时时间，单位：秒（支持浮点型，如 1.5 表示 1s+500ms，-1 表示永不超时）
     * @param bool $isPrintLog 是否打印日志，true 打印，false 不打印
     * @return null
     */
    public function channelLock(string $key, callable $businessLogic, float $timeoutSeconds = -1, bool $isPrintLog = false)
    {
        return ChannelLock::getInstance()->synchronized($key, $businessLogic, $timeoutSeconds, $isPrintLog);
    }

    /**
     * @param Redis $redis redis实例对象
     * @param string $key 锁的名称
     * @param callable $businessLogic 业务逻辑代码
     * @param float $timeoutSeconds 超时时间，单位：秒（支持浮点型，如 1.5 表示 1s+500ms）
     * @param string|null $token 锁的值
     * @param LoopInterface|null $loop 循环器
     * @return mixed
     * @throws Exception\MutexException
     * @throws \Throwable
     */
    public function redisLock($redis, string $key, callable $businessLogic, float $timeoutSeconds = 5, ?string $token = null, ?LoopInterface $loop = null)
    {
        return (new RedisLock($redis, $key, $timeoutSeconds, $token, $loop))->synchronized($businessLogic);
    }

    /**
     * @param array<Redis|RedisCluster> $redisInstances redis实例对象数组
     * @param string $key 锁的名称
     * @param callable $businessLogic 业务逻辑代码
     * @param float $timeoutSeconds 超时时间，单位：秒（支持浮点型，如 1.5 表示 1s+500ms）
     * @param string|null $token 锁的值
     * @param LoopInterface|null $loop 循环器
     * @return mixed
     * @throws Exception\MutexException
     * @throws \Throwable
     */
    public function redLock(array $redisInstances, string $key, callable $businessLogic, float $timeoutSeconds = 5, ?string $token = null, ?LoopInterface $loop = null)
    {
        return (new RedLock($redisInstances, $key, $timeoutSeconds, $token, $loop))->synchronized($businessLogic);
    }

}
