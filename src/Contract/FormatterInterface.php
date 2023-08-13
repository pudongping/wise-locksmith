<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-13 11:38
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Contract;

use Throwable;

interface FormatterInterface
{

    public function format(Throwable $throwable): string;

}