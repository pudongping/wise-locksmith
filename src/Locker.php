<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-15 15:12
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith;

use Psr\Log\LoggerInterface;

class Locker
{

    public function setLogger(LoggerInterface $logger): Locker
    {
        Log::getInstance()->setLogger($logger);

        return $this;
    }

}
