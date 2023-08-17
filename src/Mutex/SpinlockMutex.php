<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-17 11:04
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Mutex;

use Pudongping\WiseLocksmith\Contract\LoopInterface;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Exception\LockReleaseException;
use Pudongping\WiseLocksmith\Loop\ExponentialBackoffLooper;
use Pudongping\WiseLocksmith\Exception\WiseLocksmithException;
use function Pudongping\WiseLocksmith\Support\create_token;

abstract class SpinlockMutex extends LockMutex
{

    /**
     * 锁的名称
     *
     * @var string
     */
    protected $key;

    /**
     * 锁的值
     *
     * @var string
     */
    protected $token;

    /**
     * 锁的超时时间（自动过期时间），单位：秒
     * 支持浮点数，eg：1.5 = 1500ms
     *
     * @var float
     */
    protected $timeoutSeconds;

    /**
     * 循环器
     *
     * @var LoopInterface
     */
    private $loop;

    /**
     * 获取到锁时的时间
     *
     * @var float
     */
    private $acquiredTime;

    public function __construct(
        string         $key,
        float          $timeoutSeconds,
        ?string        $token = null,
        ?LoopInterface $loop = null
    ) {
        $this->key = $key;
        $this->timeoutSeconds = $timeoutSeconds;

        $this->setToken($token)->setLoop($loop);
    }

    /**
     * @param LoopInterface|null $loop
     * @return self
     * @throws WiseLocksmithException
     */
    private function setLoop(?LoopInterface $loop): self
    {
        if (is_null($loop)) {
            $loop = new ExponentialBackoffLooper($this->timeoutSeconds);
        }

        $this->loop = $loop;

        return $this;
    }

    /**
     * @param string|null $token
     * @return self
     */
    private function setToken(?string $token): self
    {
        if (! $token) {
            $token = create_token();
        }

        $this->token = $token;

        return $this;
    }

    abstract protected function lock(string $key, float $timeoutSeconds, string $token): bool;

    abstract protected function unlock(string $key, string $token): bool;

    protected function acquire()
    {
        $this->loop->execute(function (): void {
            // 抢占到锁的时间
            $this->acquiredTime = microtime(true);

            // 锁的过期时间增加了一秒，以确保我们只删除我们自己的键。
            // 这将防止出现这样的情况：在超时之前，此键过期，另一个进程成功获取相同的键，然后由此进程删除。
            if ($this->lock($this->key, $this->timeoutSeconds + 1, $this->token)) {
                // 已经抢占到了锁，告知外层，循环停止
                $this->loop->end();
            }

        });
    }

    public function release()
    {
        // 从抢占到锁到准备释放锁时，耗时多少秒
        $elapsedTime = microtime(true) - $this->acquiredTime;

        // 锁已经因为超时而过期（自动释放）
        if ($elapsedTime > $this->timeoutSeconds) {
            throw new LockReleaseException(
                ErrorCode::ERROR,
                sprintf(
                    'The code executed for %.2f seconds. But the timeout is %.2f ' .
                    'seconds. The last %.2f seconds were executed outside of the lock.',
                    $elapsedTime,
                    $this->timeoutSeconds,
                    $elapsedTime - $this->timeoutSeconds
                )
            );
        }

        // 最坏的情况是在键过期前一秒。这确保我们不会删除错误的键。
        if (! $this->unlock($this->key, $this->token)) {
            throw new LockReleaseException(ErrorCode::ERROR, 'Failed to release the lock.');
        }

    }

}
