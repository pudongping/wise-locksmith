<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-12 16:13
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Exception;

use Exception;
use Throwable;

class WiseLocksmithException extends Exception
{

    public function __construct(int $code = 0, string $message = '', Throwable $previous = null)
    {
        [$code, $message] = ErrorCode::codeMsg($code, $message);
        parent::__construct($message, $code, $previous);
    }

}