<?php
/**
 * 协程级的锁机制
 * see https://wiki.swoole.com/#/coroutine/channel?id=coroutinechannel
 * Thanks for https://www.easyswoole.com/Components/Component/channelLock.html
 *
 * 注意：
 * 1. 只能在同一进程的不同协程内使用。
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 11:32
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Swoole;

use Swoole\Coroutine\Channel;
use Pudongping\WiseLocksmith\Traits\StaticInstance;
use Pudongping\WiseLocksmith\Swoole\SwooleEngine as Coroutine;
use Pudongping\WiseLocksmith\Exception\CoroutineException;
use Pudongping\WiseLocksmith\Exception\ErrorCode;
use Pudongping\WiseLocksmith\Log;

class ChannelLock
{

    use StaticInstance;

    protected $channelList = [];

    /**
     * 标记锁的状态
     *
     * @var array
     */
    protected $lockStatus = [];

    /**
     * 锁的内容，其实这里的内容是什么并不太重要，只要尽可能的不为「零值」即可（避免造成歧义）
     * 比如：空字符串 ''、false、null、0、空数组 []
     *
     * @var string
     */
    private static $lockContent = 'wise_locksmith_channel_lock';

    /**
     * 加锁
     *
     * 当调用此函数后，会尝试锁住 $key ，锁成功时，将会返回 true
     * 如果之前已经有其他协程锁住了此 $key 那么程序将会阻塞，直到达到 $timeout 超时，然后返回 false
     * $timeout = -1 表示永不超时，也就是会永久阻塞
     *
     * @param string $key 锁的名称
     * @param float $timeout 设置超时时间，单位：秒（支持浮点型，如 1.5 表示 1s+500ms，-1 表示永不超时）
     * @param bool $isPrintLog 是否打印日志
     * @return bool
     * @throws CoroutineException
     */
    public function lock(string $key, float $timeout = -1, bool $isPrintLog = false): bool
    {
        $this->runInSwoole();

        $cid = Coroutine::id();
        if (isset($this->lockStatus[$cid])) {
            // 当前协程已经抢占到了锁
            return true;
        }

        if (! isset($this->channelList[$key])) {
            // 设定缓冲区容量为 1，从而达到多协程互斥的效果
            $this->channelList[$key] = new Channel(1);
        }

        /*** @var Channel $chan */
        $chan = $this->channelList[$key];

        // 在通道已满的情况下，push 会挂起当前协程，在约定的时间内，如果没有任何消费者消费数据，将发生超时，
        // 底层会恢复当前协程，push 调用立即返回 false，写入失败
        $res = $chan->push(self::$lockContent, $timeout);
        if ($res) {  // 执行成功返回 true
            // 标记当前协程已经上锁
            $this->lockStatus[$cid] = true;
            $isPrintLog && Log::getInstance()->logger()->debug("ChannelLock lock cid={$cid} has locked");
        } else {  // 通道被关闭时，执行失败返回 false
            $errCode = $chan->errCode;
            $isPrintLog && Log::getInstance()->logger()->debug("ChannelLock lock cid={$cid} code={$errCode}");
        }

        return $res;
    }

    /**
     * 解锁
     *
     * @param string $key 锁的名称
     * @param float $timeout 设置超时时间，单位：秒（支持浮点型，如 1.5 表示 1s+500ms，-1 表示永不超时）
     * @param bool $isPrintLog 是否打印日志
     * @return bool 是否存在锁，已经解锁（不存在锁）true，未解锁（当前锁还存在）false
     * @throws CoroutineException
     */
    public function unlock(string $key, float $timeout = -1, bool $isPrintLog = false): bool
    {
        $this->runInSwoole();

        $cid = Coroutine::id();
        if (! isset($this->lockStatus[$cid])) {
            $isPrintLog && Log::getInstance()->logger()->debug("ChannelLock unlock cid={$cid} don't locked");
            return true;
        }

        if (! isset($this->channelList[$key])) {
            unset($this->lockStatus[$cid]);
            $isPrintLog && Log::getInstance()->logger()->debug("ChannelLock unlock cid={$cid} has lockStatus but the key={$key} not in channelList");
            return false;
        }

        /*** @var Channel $chan */
        $chan = $this->channelList[$key];

        if ($chan->isEmpty()) {
            unset($this->lockStatus[$cid]);
            $isPrintLog && Log::getInstance()->logger()->debug("ChannelLock unlock cid={$cid} channel is empty");
            return true;
        } else {
            $res = $chan->pop($timeout);
            if ($res) {
                unset($this->lockStatus[$cid]);
                $isPrintLog && Log::getInstance()->logger()->debug("ChannelLock unlock cid={$cid} pop success res={$res}");
            }

            return $res === self::$lockContent;
        }

    }

    /**
     * 尝试锁住 $key 并在协程结束后自动解锁
     *
     * @param string $key
     * @param float $timeout
     * @return bool
     * @throws CoroutineException
     */
    public function deferLock(string $key, float $timeout = -1): bool
    {
        $locked = $this->lock($key, $timeout);
        if ($locked) {
            Coroutine::defer(function () use ($key) {
                $this->unlock($key);
            });
        }

        return $locked;
    }

    /**
     * @return void
     * @throws CoroutineException
     */
    protected function runInSwoole()
    {
        $isIn = extension_loaded('swoole') && Coroutine::inCoroutine();
        if (! $isIn) {
            throw new CoroutineException(ErrorCode::ERR_CO_NON_COROUTINE_ENV);
        }
    }

}