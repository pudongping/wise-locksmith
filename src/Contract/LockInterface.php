<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-12 16:40
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Contract;

interface LockInterface
{

    public function acquire();

    public function release();

}