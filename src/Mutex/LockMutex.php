<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-17 14:13
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Mutex;

use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\MutexException;
use Throwable;

abstract class LockMutex
{

    /**
     * 尝试获取锁
     *
     * @return void
     */
    abstract protected function acquire();

    /**
     * 释放锁
     *
     * @return void
     */
    abstract protected function release();

    /**
     * 同步互斥安全执行业务代码
     *
     * @param callable $businessLogic
     * @return mixed
     * @throws MutexException
     * @throws Throwable
     */
    public function synchronized(callable $businessLogic)
    {
        $this->acquire();

        $codeResult = null;
        $codeException = null;

        try {
            $codeResult = $businessLogic();
        } catch (Throwable $exception) {
            $codeException = $exception;

            throw $exception;
        } finally {
            try {
                $this->release();
            } catch (Throwable $exception) {
                $e = new MutexException(ErrorCode::ERROR, 'Failed to release the lock.', $exception);
                $e->setCodeResult($codeResult)->setCodeException($codeException);

                throw $e;
            }
        }

        return $codeResult;
    }

}
