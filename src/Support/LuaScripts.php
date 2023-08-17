<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 23:52
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Support;

class LuaScripts
{

    public static function release()
    {
        return <<<'LUA'
if redis.call("GET",KEYS[1]) == ARGV[1] then
    return redis.call("DEL",KEYS[1])
else
    return 0
end
LUA;
    }

}
