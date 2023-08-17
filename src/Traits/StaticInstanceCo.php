<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 17:27
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Traits;

use Pudongping\WiseLocksmith\Support\Swoole\Context;

trait StaticInstanceCo
{

    /**
     * @param array $params
     * @param bool $refresh
     * @param string $suffix
     * @return false|mixed|static|null
     */
    public static function getInstance(array $params = [], bool $refresh = false, string $suffix = '')
    {
        $key = get_called_class() . $suffix;

        $instance = null;
        if (Context::has($key)) {
            $instance = Context::get($key);
        }

        if ($refresh || ! $instance instanceof static) {
            $instance = new static(...$params);
            Context::set($key, $instance);
        }

        return $instance;
    }

}
