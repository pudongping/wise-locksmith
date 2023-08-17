<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-17 16:04
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Loop;

use Pudongping\WiseLocksmith\Contract\LoopInterface;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\WiseLocksmithException;

abstract class AbstractLoop implements LoopInterface
{

    /**
     * 过期时间，单位，秒
     * 支持浮点数，eg：1.5 = 1500ms
     *
     * @var float
     */
    protected $timeoutSeconds;

    /**
     * 在阻塞时重新尝试获取锁之前等待的秒数
     * 支持浮点数，eg：0.25 = 250ms
     *
     * @var float
     */
    protected $sleepSeconds;

    /**
     * 当前代码正在循环执行时为 true
     *
     * @var bool
     */
    protected $looping = false;

    /**
     * 标记当前的循环执行结束
     *
     * @return void
     */
    public function end()
    {
        $this->looping = false;
    }

    abstract public function execute(callable $operation);

    protected function validateTimeoutSeconds(float $timeoutSeconds)
    {
        if ($timeoutSeconds <= 0) {
            throw new WiseLocksmithException(ErrorCode::ERROR, sprintf(
                'The timeout must be greater than 0. %f was given.',
                $timeoutSeconds
            ));
        }
    }

    protected function validateSleepSeconds(float $sleepSeconds)
    {
        if ($sleepSeconds <= 0) {
            throw new WiseLocksmithException(ErrorCode::ERROR, sprintf(
                'The sleep must be greater than 0. %f was given.',
                $sleepSeconds
            ));
        }
    }

}
