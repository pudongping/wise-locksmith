<h1 align="center">wise-locksmith</h1>

<p align="center">

[![Latest Stable Version](https://poser.pugx.org/pudongping/wise-locksmith/v/stable.svg)](https://packagist.org/packages/pudongping/wise-locksmith) 
[![Total Downloads](https://poser.pugx.org/pudongping/wise-locksmith/downloads.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Latest Unstable Version](https://poser.pugx.org/pudongping/wise-locksmith/v/unstable.svg)](https://packagist.org/packages/pudongping/wise-locksmith)
[![Minimum Redis Version](http://img.shields.io/badge/redis-%3E%3D%202.6.12-cb4637.svg)](https://redis.io/commands/set/)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![Packagist](https://img.shields.io/packagist/v/pudongping/wise-locksmith.svg)](https://github.com/pudongping/wise-locksmith)
[![License](https://img.shields.io/badge/license-MIT-blue)](https://packagist.org/packages/pudongping/wise-locksmith)

</p>

:lock: 一个与框架无关的锁库，用于提供 PHP 代码的序列化执行。

## Quickstart

## Requirements

- PHP Version >= 7.1
- Redis Version >= 2.6.12

## Installation

```shell
composer require pudongping/wise-locksmith
```

## Tests

To run the test suite, clone this repository and then install dependencies via Composer:

```sh
composer install
```

Then, go to the project root and run:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit -c ./phpunit.xml.dist

or 

composer run test

```

## Acknowledgments

### Library

- [redisson/redisson](https://github.com/redisson/redisson)
- [php-lock/lock](https://github.com/php-lock/lock)
- [laravel/framework](https://github.com/laravel/framework)
- [easy-swoole/component](https://github.com/easy-swoole/component)
- [hyperf/context](https://github.com/hyperf/context)
- [swoole/library](https://github.com/swoole/library)

### Posts

- [How to do distributed locking](https://martin.kleppmann.com/2016/02/08/how-to-do-distributed-locking.html)
- [Is Redlock safe?](http://antirez.com/news/101)
- [Distributed Locks with Redis](https://redis.io/docs/manual/patterns/distributed-locks/) ， [一种基于 Redis 的分布式锁模式
  （中文译文）](https://learnku.com/database/t/71960)

## Contributing

Bug reports (and small patches) can be submitted via the [issue tracker](https://github.com/pudongping/wise-locksmith/issues). Forking the repository and submitting a Pull Request is preferred for substantial patches.

## License

MIT, see [LICENSE file](LICENSE).
