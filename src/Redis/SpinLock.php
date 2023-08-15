<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 15:54
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Redis;

class SpinLock
{

    function lock_spin(callable $callBack, string $key, int $counter = 10, int $expireTime = 5, int $loopWaitTime = 500000, bool $writeLog = true)
    {
        $result = null;
        while ($counter > 0) {
            $val = microtime() . '_' . uniqid('', true);
            $noticeLog = compact('key', 'val', 'expireTime', 'loopWaitTime', 'counter');
            $writeLog && logger()->notice(__FUNCTION__ . ' ====> ', $noticeLog);
            if (redis()->set($key, $val, ['NX', 'EX' => $expireTime])) {
                if (redis()->get($key) === $val) {
                    try {
                        $result = $callBack();
                    } finally {
                        $delKeyLua = 'if redis.call("GET", KEYS[1]) == ARGV[1] then return redis.call("DEL", KEYS[1]) else return 0 end';
                        redis()->eval($delKeyLua, [$key, $val], 1);
                        $writeLog && logger()->notice(__FUNCTION__ . ' delete key ====> ', $noticeLog);
                    }
                    return $result;
                }
            }
            $counter--;
            usleep($loopWaitTime);
        }
        return $result;
    }

}
