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
    public const ERR_LOCKED_NOT_BELONG_TO_MYSELF = 20003;
    public const ERR_LOCK_TIMEOUT = 20004;

    public static $errMaps = [
        self::ERROR => 'Wise Locksmith library has error.',
        self::ERR_CO_NON_COROUTINE_ENV => 'Non-Coroutine environment.',
        self::ERR_CO_DESTROYED => 'Coroutine has been destroyed.',
        self::ERR_LOCKED_NOT_BELONG_TO_MYSELF => 'Acquire locked, but not belong to myself.',
        self::ERR_LOCK_TIMEOUT => 'Lock timeout',
    ];

    public static function codeMsg(int $code = 0, string $msg = '')
    {
        0 === $code && $code = self::ERROR;
        '' === $msg && $msg = self::$errMaps[$code] ?? 'Wise Locksmith library has error.';
        return [$code, $msg];
    }

}
