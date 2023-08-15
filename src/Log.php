<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-14 02:20
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith;

use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Pudongping\WiseLocksmith\Contract\FormatterInterface;
use Pudongping\WiseLocksmith\Traits\StaticInstance;

class Log implements FormatterInterface, LoggerAwareInterface
{

    use StaticInstance;
    use LoggerAwareTrait;

    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        if (is_null($this->logger)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    public function format(Throwable $throwable): string
    {
        return (string)$throwable;
    }

}
