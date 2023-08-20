<h1 align="center">wise-locksmith</h1>

<p align="center">

[![Latest Stable Version](https://poser.pugx.org/pudongping/wise-locksmith/v/stable.svg)](https://packagist.org/packages/pudongping/wise-locksmith) 
[![Total Downloads](https://poser.pugx.org/pudongping/wise-locksmith/downloads.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Latest Unstable Version](https://poser.pugx.org/pudongping/wise-locksmith/v/unstable.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![Packagist](https://img.shields.io/packagist/v/pudongping/wise-locksmith.svg)](https://github.com/pudongping/wise-locksmith)
[![License](https://poser.pugx.org/pudongping/wise-locksmith/license)](https://packagist.org/packages/pudongping/wise-locksmith)

</p>

English | [中文](./README-ZH.md)

:lock: An independent mutex library for PHP, providing serialized execution of PHP code in high-concurrency scenarios, independent of the framework.

## Requirements

- PHP >= 7.1 or above
- Redis >= 2.6.12 or above (required for distributed locks or Redlock)
- Swoole >= 4.5 or above (required for coroutine-level mutex locks)

## Installation

```shell
composer require pudongping/wise-locksmith
```

## Quickstart

```php
<?php

require 'vendor/autoload.php';

use Pudongping\WiseLocksmith\Locker;

$redisHosts = [
    [
        'host' => '127.0.0.1',
        'port' => 6379
    ],
    [
        'host' => '127.0.0.1',
        'port' => 6380
    ],
    [
        'host' => '127.0.0.1',
        'port' => 6381
    ],
    [
        'host' => '127.0.0.1',
        'port' => 6382
    ],
    [
        'host' => '127.0.0.1',
        'port' => 6383
    ],
];

// Initialize Redis instances if distributed locks or Redlock are needed; otherwise, you can skip this step
$redisInstances = array_map(function ($v) {
    $redis = new \Redis();
    $redis->connect($v['host'], $v['port']);
    return $redis;
}, $redisHosts);

// Create an instance of locker
$locker = new Locker();
```

## Usage

An example of potential data inconsistency in high-concurrency scenarios is provided in the current project, [see here](./examples).

### flock - File Lock

File locking has no dependencies. You can set the lock's timeout using the optional third parameter, in seconds (supports floating-point numbers, e.g., 1.5 means 1500ms, which means it will wait for a maximum of 1500ms; if it fails to acquire the lock, it will actively release the attempt and throw a `Pudongping\WiseLocksmith\Exception\TimeoutException` exception).
Setting it to `Pudongping\WiseLocksmith\Lock\File\Flock::INFINITE_TIMEOUT` means it never expires, and it will continuously block attempting to acquire the lock until successful. The default value is `Pudongping\WiseLocksmith\Lock\File\Flock::INFINITE_TIMEOUT`.

```php

$path = tempnam(sys_get_temp_dir(), 'wise-locksmith-flock-');
$fileHandler = fopen($path, 'r');

$res = $locker->flock($fileHandler, function () {
    // Write the code you want to protect here
});

unlink($path);

return $res;
```

### redisLock - Distributed Lock

Requires the `redis` extension. You can set the lock's timeout using the optional third parameter, in seconds (supports floating-point numbers, e.g., 1.5 means 1500ms, which means it will wait for a maximum of 1500ms; if it fails to acquire the lock, it will actively release the attempt and throw a `Pudongping\WiseLocksmith\Exception\TimeoutException` exception).
The default value is `5`. The fourth parameter is a unique value for the current lock, which is generally not needed to be set unless there are special circumstances.

```php

$res = $locker->redisLock($redisInstances[0], 'redisLock', function () {
    // Write the code you want to protect here
});

return $res;
```

### redLock - RedLock (Implementation of distributed locks in a Redis cluster environment)

The parameters required for setting up the `redLock` lock are identical to the `redisLock` lock, except for the first parameter. All other parameters are exactly the same. The `redLock` lock is a cluster implementation of the `redisLock` lock.

```php

$res = $locker->redLock($redisInstances, 'redLock', function () {
    // Write the code you want to protect here
});

return $res;
```

### channelLock - Coroutine-Level Mutex Lock

When using this lock, you need to have the `swoole` extension installed, and the version must be greater than or equal to `4.5`. You can set the lock's timeout using the optional third parameter, in seconds (supports floating-point numbers, e.g., 1.5 means 1500ms, which means it will wait for a maximum of 1500ms; if it fails to acquire the lock, it will actively give up the attempt and return `false` to indicate the lock was not acquired).
Setting it to `-1` means it never expires, and it will continuously block attempting to acquire the lock until successful. The default value is `-1`.

```php

$res = $locker->channelLock('channelLock', function () {
    // Write the code you want to protect here
});

return $res;
```

For all the mentioned locks, the business closure function is executed only after successfully acquiring the lock. It then returns the result of the business closure function's execution. Otherwise, if the lock is not acquired, the business closure function is not executed, and the return value is `null`.

## Running tests

To run the test suite, clone this repository and then install dependencies via Composer.

```sh
composer install
```

Then, go to the project root and run:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit -c ./phpunit.xml.dist

or 

composer run test
```

## Exception Handling

You can catch `Pudongping\WiseLocksmith\Exception\WiseLocksmithException` exceptions to capture all exceptions thrown by this library.

```php
use Pudongping\WiseLocksmith\Exception\WiseLocksmithException;
use Pudongping\WiseLocksmith\Locker;

try {
    $locker = new Locker();
    // ...
} catch (WiseLocksmithException $exception) {
    var_dump($exception->getPrevious());
    var_dump($exception->getCode(), $exception->getMessage());
}

```

## Acknowledgments

- [laravel/framework](https://github.com/laravel/framework)
- [hyperf/context](https://github.com/hyperf/context)
- [easy-swoole/component](https://github.com/easy-swoole/component)
- [swoole/library](https://github.com/swoole/library)
- [redisson/redisson](https://github.com/redisson/redisson)
- [php-lock/lock](https://github.com/php-lock/lock)
- [How to do distributed locking](https://martin.kleppmann.com/2016/02/08/how-to-do-distributed-locking.html)
- [Is Redlock safe?](http://antirez.com/news/101)
- [Distributed Locks with Redis](https://redis.io/docs/manual/patterns/distributed-locks/) ， [一种基于 Redis 的分布式锁模式
  （中文译文）](https://learnku.com/database/t/71960)

## Contributing

Bug reports (and small patches) can be submitted via the [issue tracker](https://github.com/pudongping/wise-locksmith/issues). Forking the repository and submitting a Pull Request is preferred for substantial patches.

## License

MIT, see [LICENSE file](LICENSE).
