<h1 align="center">wise-locksmith</h1>

<p align="center">

[![Latest Stable Version](https://poser.pugx.org/pudongping/wise-locksmith/v/stable.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Total Downloads](https://poser.pugx.org/pudongping/wise-locksmith/downloads.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Latest Unstable Version](https://poser.pugx.org/pudongping/wise-locksmith/v/unstable.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![Packagist](https://img.shields.io/packagist/v/pudongping/wise-locksmith.svg)](https://github.com/pudongping/wise-locksmith)
[![License](https://poser.pugx.org/pudongping/wise-locksmith/license)](https://packagist.org/packages/pudongping/wise-locksmith)

</p>

[English](./README.md) | 中文

:lock: 一个与框架无关的互斥锁库，用于在高并发场景下提供 PHP 代码的序列化执行。

## 要求

- PHP >= 7.1 或以上版本
- Redis >= 2.6.12 或以上版本（如果需要使用到分布式锁或者红锁的情况下）
- Swoole >= 4.5 或以上版本 （如果需要使用协程级别的互斥锁的情况下）

## 安装

```shell
composer require pudongping/wise-locksmith
```

## 快速开始

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

// 如果需要使用到分布式锁或者红锁时，则需要初始化 redis 实例，否则可跳过这一步
$redisInstances = array_map(function ($v) {
    $redis = new \Redis();
    $redis->connect($v['host'], $v['port']);
    return $redis;
}, $redisHosts);

// 创建一个锁实例
$locker = new Locker();
```

## 使用

当前项目下提供了一个高并发场景下可能导致数据不一致的示例，[详见](./examples)。

### flock - 文件锁

文件锁没有任何依赖。可通过可选的第 3 个参数参数设置锁的超时时间，单位：秒。（支持浮点型，比如 1.5 表示 1500ms 也就是最多会等待 1500ms，如果没有抢占到锁，那么则主动放弃抢锁，同时会抛出 `Pudongping\WiseLocksmith\Exception\TimeoutException` 异常）
设置成 `Pudongping\WiseLocksmith\Lock\File\Flock::INFINITE_TIMEOUT` 时，表示永不过期，则当前一直会阻塞式抢占锁，直到抢占到锁为止。默认值为：`Pudongping\WiseLocksmith\Lock\File\Flock::INFINITE_TIMEOUT`。

```php

$path = tempnam(sys_get_temp_dir(), 'wise-locksmith-flock-');
$fileHandler = fopen($path, 'r');

$res = $locker->flock($fileHandler, function () {
    // 这里写你想保护的代码
});

unlink($path);

return $res;
```

### redisLock - 分布式锁

需要依赖 `redis` 扩展。可通过可选的第 3 个参数设置锁的超时时间，单位：秒。（支持浮点型，比如 1.5 表示 1500ms 也就是最多会等待 1500ms，如果没有抢占到锁，那么则主动放弃抢锁，同时会抛出 `Pudongping\WiseLocksmith\Exception\TimeoutException` 异常）
默认值为：`5`。第 4 个参数为当前锁的具有唯一性的值，除非有特殊情况下需要设置，一般不需要设置。

```php

$res = $locker->redisLock($redisInstances[0], 'redisLock', function () {
    // 这里写你想保护的代码
});

return $res;
```

### redLock - 红锁（redis 集群环境时，分布式锁的实现）

redLock 锁所需要设置的参数和 redisLock 锁除了第一个参数有区别以外，其他几个参数完全一致。redLock 锁是 redisLock 锁的集群实现。

```php

$res = $locker->redLock($redisInstances, 'redLock', function () {
    // 这里写你想保护的代码
});

return $res;
```

### channelLock - 协程级别的互斥锁

使用此锁时，需要安装 `swoole` 扩展。且版本必须大于等于 `4.5`。可通过可选的第 3 个参数设置锁的超时时间，单位：秒。（支持浮点型，比如 1.5 表示 1500ms 也就是最多会等待 1500ms，如果没有抢占到锁，那么则主动放弃抢锁，同时直接返回 `false` 表示没有抢占到锁）
设置成 `-1` 时，表示永不过期，则当前一直会阻塞式抢占锁，直到抢占到锁为止。默认值为：`-1`。

```php

$res = $locker->channelLock('channelLock', function () {
    // 这里写你想保护的代码
});

return $res;
```

以上几种锁，都只有抢占到了锁后才会执行业务闭包函数，然后返回业务闭包函数的执行结果。否则，没有抢占到锁时，业务闭包函数不会执行，返回值直接返回 `null`。

## 运行测试

要运行测试，需要先克隆此存储库，然后通过 Composer 安装依赖项。

```sh
composer install
```

然后，到项目根目录下执行

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit -c ./phpunit.xml.dist

或者

composer run test
```

## 异常捕获

你可以通过 catch `Pudongping\WiseLocksmith\Exception\WiseLocksmithException` 异常，来捕获此库中所有的异常。

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

## 致谢

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

## 贡献

Bug 报告(或者小补丁)可以通过 [issue tracker](https://github.com/pudongping/wise-locksmith/issues) 提交。对于大量的补丁，最好对库进行 Fork 并提交 Pull Request。

## License

MIT, see [LICENSE file](LICENSE).
