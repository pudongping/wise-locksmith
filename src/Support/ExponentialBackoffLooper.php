<?php
/**
 * 指数退避 https://en.wikipedia.org/wiki/Exponential_backoff
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-16 20:44
 */
declare(strict_types=1);

namespace Pudongping\WiseLocksmith\Support;

class ExponentialBackoffLooper
{

    private const MINIMUM_WAIT_US = 1e4; // 0.01 seconds

    private const MAXIMUM_WAIT_US = 5e5; // 0.50 seconds

    private $timeout;


}
