<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-12 16:16
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Exception;

use Throwable;

class MutexException extends WiseLocksmithException
{

    private $codeResult;

    private $codeException;

    public function setCodeResult($codeResult): self
    {
        $this->codeResult = $codeResult;

        return $this;
    }

    public function getCodeResult()
    {
        return $this->codeResult;
    }

    public function setCodeException(Throwable $codeException): self
    {
        $this->codeException = $codeException;

        return $this;
    }

    public function getCodeException(): ?Throwable
    {
        return $this->codeException;
    }

}
