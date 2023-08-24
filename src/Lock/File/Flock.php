<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-19 01:40
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Lock\File;

use Pudongping\WiseLocksmith\Contract\LoopInterface;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\LockAcquireException;
use Pudongping\WiseLocksmith\Exception\LockReleaseException;
use Pudongping\WiseLocksmith\Exception\InvalidArgumentException;
use Pudongping\WiseLocksmith\Loop\ExponentialBackoffLooper;
use Pudongping\WiseLocksmith\Mutex\LockMutex;

class Flock extends LockMutex
{

    /**
     * 默认永不超时（一直阻塞）
     */
    public const INFINITE_TIMEOUT = -1;

    /**
     * 文件资源对象
     *
     * @var resource
     */
    private $fileHandle;

    /**
     * 超时时间，单位，秒
     *
     * @var float
     */
    private $timeoutSeconds;

    /**
     * 循环器
     *
     * @var null|LoopInterface
     */
    private $loop;

    public function __construct(
        $fileHandle,
        float $timeoutSeconds = self::INFINITE_TIMEOUT,
        ?LoopInterface $loop = null
    ) {
        if (! is_resource($fileHandle)) {
            throw new InvalidArgumentException(ErrorCode::ERROR, 'The file handle is not a valid resource.');
        }

        $this->fileHandle = $fileHandle;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->loop = $loop;
    }

    /**
     * 阻塞式抢占锁
     *
     * @return void
     * @throws LockAcquireException
     */
    private function lockBlocking()
    {
        // 独占锁
        if (! flock($this->fileHandle, LOCK_EX)) {
            throw new LockAcquireException(ErrorCode::ERROR, 'Failed to lock the file. By lockBlocking func.');
        }
    }

    /**
     * 通过循环的方式获取锁
     *
     * @return void
     * @throws \Pudongping\WiseLocksmith\Exception\TimeoutException
     * @throws \Pudongping\WiseLocksmith\Exception\WiseLocksmithException
     */
    private function lockBusy()
    {
        if (is_null($this->loop)) {
            $this->loop = new ExponentialBackoffLooper($this->timeoutSeconds);
        }

        $this->loop->execute(function (): void {
            if ($this->acquireNonBlockingLock()) {
                $this->loop->end();
            }
        });

    }

    /**
     * 非阻塞式获取锁
     *
     * @return bool
     * @throws LockAcquireException
     */
    private function acquireNonBlockingLock(): bool
    {
        // 在尝试获取文件锁时，以非阻塞的方式进行操作。这意味着如果无法立即获取锁，函数不会阻塞进程/线程，而是
        // 立即返回。这样当其他进程/线程获取到锁时，避免当前进程/线程在等待锁时一直被阻塞。
        // 当前进程/线程能够立即获取到锁时，为 true
        // 当前进程/线程无法获取到锁时，为 false，如果是因为其他进程/线程抢占到了锁，导致当前进程/线程抢锁失败时，$wouldBlock === 1
        if (! flock($this->fileHandle, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if ($wouldBlock) {
                // 其他进程抢占到了该锁，导致当前进程无法获得锁
                return false;
            }
            throw new LockAcquireException(ErrorCode::ERROR, 'Failed to lock the file. By acquireNonBlockingLock func.');
        }

        return true;
    }

    public function acquire()
    {
        if (self::INFINITE_TIMEOUT == $this->timeoutSeconds) {
            $this->lockBlocking();
            return;
        }

        $this->lockBusy();
    }

    public function release()
    {
        if (! flock($this->fileHandle, LOCK_UN)) {
            throw new LockReleaseException(ErrorCode::ERROR, 'Failed to unlock the file.');
        }
    }

}
