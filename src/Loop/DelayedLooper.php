<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-17 15:59
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Loop;

use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\TimeoutException;
use function Pudongping\WiseLocksmith\Support\wait;

class DelayedLooper extends AbstractLoop
{

    /**
     * @param float $timeoutSeconds
     * @param float $sleepSeconds
     * @throws \Pudongping\WiseLocksmith\Exception\WiseLocksmithException
     */
    public function __construct(float $timeoutSeconds = 3, float $sleepSeconds = 0.25)
    {
        $this->validateTimeoutSeconds($timeoutSeconds);
        $this->validateSleepSeconds($sleepSeconds);

        $this->timeoutSeconds = $timeoutSeconds;
        $this->sleepSeconds = $sleepSeconds;
    }

    public function execute(callable $operation)
    {
        // 标记开始循环
        $this->looping = true;

        // 计算超时时间节点
        $deadline = microtime(true) + $this->timeoutSeconds;

        // 回调函数的执行结果
        $result = null;

        while ($this->looping && microtime(true) < $deadline) {

            $result = $operation();

            if (! $this->looping) {
                // 回调函数 $operation 已调用 $this->end() 并成功获取了锁。
                return $result;
            }

            // 计算剩余的最大时间，不能超过有效期。单位，秒
            $usecRemaining = $deadline - microtime(true);

            // 当前已经迭代超时
            if ($usecRemaining <= 0) {
                throw new TimeoutException(ErrorCode::ERR_LOCK_TIMEOUT, sprintf('Timeout of %f seconds exceeded.', $this->timeoutSeconds));
            }

            // 根据指定睡眠时间睡眠程序
            wait($this->sleepSeconds);
        }

        throw new TimeoutException(ErrorCode::ERR_LOCK_TIMEOUT, sprintf('Timeout of %f seconds exceeded.', $this->timeoutSeconds));
    }

}
