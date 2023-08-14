<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 14:30
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Traits;

trait StaticInstance
{

    private static $instance;

    protected function __construct()
    {

    }

    private function __clone()
    {

    }

    private function __wakeup()
    {

    }

    /**
     * @param array $params
     * @param bool $refresh
     * @return static
     */
    public static function getInstance(array $params = [], bool $refresh = false)
    {
        if ($refresh || ! static::$instance instanceof static) {
            static::$instance = new static(...$params);
        }

        return static::$instance;
    }

}