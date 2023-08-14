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
use Pudongping\WiseLocksmith\Contract\FormatterInterface;
use Pudongping\WiseLocksmith\Traits\StaticInstance;

class Log implements FormatterInterface
{

    use StaticInstance;

    /**
     * @var LoggerInterface
     */
    private $logger;

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

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function format(Throwable $throwable): string
    {
        // sprintf('(%s) %s: %s' . PHP_EOL . '%s' . PHP_EOL, $key, get_class($value), $value->getMessage(), $value->getTraceAsString());
        return (string)$throwable;
    }

}