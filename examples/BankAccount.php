<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-19 23:38
 */
declare(strict_types=1);

use Swoole\Coroutine;
use Pudongping\WiseLocksmith\Locker;

class BankAccount
{

    /**
     * 用户账户初始余额
     *
     * @var int
     */
    private $balance = 100;

    public function deductBalance($amount)
    {
        if ($this->balance >= $amount) {
            // 模拟业务处理耗时
            usleep(100 * 1000);
            $this->balance -= $amount;
        }

        return $this->balance;
    }

    /**
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balance;
    }

    public function dispatch(string $type, array $redisInstances = [], int $amount = 1)
    {
        // 模拟高并发场景，启动多个协程
        for ($i = 1; $i <= 150; $i++) {

            Coroutine::create(function () use ($i, $type, $amount, $redisInstances) {

                switch ($type) {
                    case 'noMutex':
                        $this->deductBalance($amount);
                        break;
                    case 'flock':
                        $this->flock($amount);
                        break;
                    case 'redisLock':
                        $this->redisLock($amount, $redisInstances[0]);
                        break;
                    case 'redLock':
                        $this->redLock($amount, $redisInstances);
                        break;
                    case 'channelLock':
                        $this->channelLock($amount);
                        break;
                    default:
                        $this->deductBalance($amount);
                        break;
                }

                $userBalance = $this->getBalance();
                echo "{$type} ====> [{$i}] 当前余额为：{$userBalance}" . PHP_EOL;
            });

        }
    }

    public function flock($amount)
    {
        $path = './alex.lock.cache';
        $fileHandler = fopen($path, 'a');
        // fwrite($fileHandler, "Locked\r\n");
        $locker = new Locker();

        $res = $locker->flock($fileHandler, function () use ($amount) {
            return $this->deductBalance($amount);
        });

        return $res;
    }

    public function redisLock($amount, $redis)
    {
        $locker = new Locker();

        $res = $locker->redisLock($redis, 'redisLock', function () use ($amount) {
            return $this->deductBalance($amount);
        }, 40);

        return $res;
    }

    public function redLock($amount, $redisInstances)
    {
        $locker = new Locker();

        $res = $locker->redLock($redisInstances, 'redLock', function () use ($amount) {
            return $this->deductBalance($amount);
        }, 40);

        return $res;
    }

    public function channelLock($amount)
    {
        $locker = new Locker();
        $res = $locker->channelLock('channelLock', function () use ($amount) {
            return $this->deductBalance($amount);
        });

        return $res;
    }

}
