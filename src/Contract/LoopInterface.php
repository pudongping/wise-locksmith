<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-17 18:06
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Contract;

interface LoopInterface
{

    public function end();

    public function execute(callable $operation);

}
