<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 17:53
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Support;

use Redis;

class RedisSupport
{

    /**
     * @param Redis $redis
     * @param string $luaScript
     * @param array $args
     * @param int $numKeys
     * @return mixed
     */
    public function runLuaScript($redis, string $luaScript, array $args = [], int $numKeys = 0)
    {
        $sha1 = sha1($luaScript);

        $redis->clearLastError();

        // 当 sha1 校验码在 redis 服务器中不存在时，会报错 `NOSCRIPT No matching script. Please use EVAL.`
        // 可以使用 $sha1 = $redis->script('LOAD', $luaScript); 提前将 lua 脚本的 sha1 缓存到 redis 服务器中
        // 或者当报错时，直接执行 eval 函数，之后再执行 evalSha 就不会报错
        // 清空所有缓存到服务器中的脚本的 sha1 使用：$redis->script('flush');
        $result = $redis->evalSha($sha1, $args, $numKeys);

        if (0 === strpos($redis->getLastError(), 'NOSCRIPT No matching script. Please use EVAL.')) {
            $result = $redis->eval($luaScript, $args, $numKeys);
        }

        return $result;
    }


}
