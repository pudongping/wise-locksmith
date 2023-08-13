<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 16:48
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Exception;

class ErrorCode
{

    public const ERROR = 20000;
    public const ERR_CO_NON_COROUTINE_ENV = 20001;
    public const ERR_CO_DESTROYED = 20002;

    public static $errMaps = [
        self::ERROR => 'Wise Locksmith library has error.',
        self::ERR_CO_NON_COROUTINE_ENV => 'Non-Coroutine environment.',
        self::ERR_CO_DESTROYED => 'Coroutine has been destroyed',
    ];

    public static function codeMsg(int $code = 0, string $msg = '')
    {
        0 === $code && $code = self::ERROR;
        '' === $msg && $msg = self::$errMaps[$code] ?? 'Wise Locksmith library has error.';
        return [$code, $msg];
    }

}