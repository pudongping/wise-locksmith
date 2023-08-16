<?php
/**
 * 指数退避 https://en.wikipedia.org/wiki/Exponential_backoff
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-16 20:44
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Support;

use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\TimeoutException;
use Pudongping\WiseLocksmith\Exception\WiseLocksmithException;

class ExponentialBackoffLooper
{

    /**
     * 锁检查的最小等待时间，单位，微秒
     */
    private const MINIMUM_WAIT_US = 1e4;  // 0.01 seconds

    /**
     * 锁检查的最大等待时间，单位，微秒
     */
    private const MAXIMUM_WAIT_US = 5e5;  // 0.50 seconds

    /**
     * 过期时间，单位，秒
     *
     * @var int
     */
    private $timeout;

    /**
     * 当前代码正在循环执行时为 true
     *
     * @var bool
     */
    private $looping = false;

    public function __construct(int $timeout = 3)
    {
        if ($timeout <= 0) {
            throw new WiseLocksmithException(ErrorCode::ERROR, sprintf(
                'The timeout must be greater than 0. %d was given.',
                $timeout
            ));
        }

        $this->timeout = $timeout;
    }

    /**
     * 标记当前的循环执行结束
     *
     * @return void
     */
    public function end()
    {
        $this->looping = false;
    }

    /**
     * 不断循环执行回调函数，直到成功或者超时
     *
     * @param callable $operation
     * @return mixed
     * @throws TimeoutException
     */
    public function execute(callable $operation)
    {
        // 标记开始循环
        $this->looping = true;

        // 计算超时时间节点
        $deadline = microtime(true) + $this->timeout;

        // 回调函数的执行结果
        $result = null;

        for ($i = 0; $this->looping && microtime(true) < $deadline; ++$i) {

            $result = $operation();

            if (! $this->looping) {
                // 回调函数 $operation 已调用 $this->end() 并成功获取了锁。
                return $result;
            }

            // 计算剩余的最大时间，不能超过有效期。单位，微秒
            $usecRemaining = intval(($deadline - microtime(true)) * 1e6);

            // 当前已经迭代超时
            if ($usecRemaining <= 0) {
                throw new TimeoutException(ErrorCode::ERROR, sprintf('Timeout of %d seconds exceeded.', $this->timeout));
            }

            // 实际睡眠的微秒数
            $usecToSleep = min($usecRemaining, $this->calculateWaitTime($i));

            usleep($usecToSleep);
        }

        throw new TimeoutException(ErrorCode::ERROR, sprintf('Timeout of %d seconds exceeded.', $this->timeout));
    }

    private function calculateWaitTime(int $retry): int
    {
        // 循环次数
        $min = min(
            (int)self::MINIMUM_WAIT_US * 1.25 ** $retry,
            self::MAXIMUM_WAIT_US
        );

        $max = min($min * 2, self::MAXIMUM_WAIT_US);

        // 等待时间具有随机性，可以减少多个线程或者进程同时唤醒的概率
        return random_int((int)$min, (int)$max);
    }

}
