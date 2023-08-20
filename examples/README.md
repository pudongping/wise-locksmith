# Example of Possible Data Inconsistency in High-Concurrency Scenarios

[English](./README.md) | 中文

## Note

Since this example uses the Swoole extension to set up an HTTP server, **it is necessary** to have the Swoole extension installed, with a version of v4.4.0 or higher, before running this example. [See more](https://wiki.swoole.com/#/coroutine/http_server?id=http%e6%9c%8d%e5%8a%a1%e5%99%a8)

## Usage

1. Modify the Redis connection configuration (if you need to test locks based on Redis).

Edit the `httpServer.php` file, and modify the connection configuration within the `$redisHosts` variable to match your own Redis configuration information. If you don't need this for testing purposes, you can set it to `$redisHosts = []`.

2. Start the HTTP server

```shell
php httpServer.php
```

3. Test Different Locks

```shell
// Test without setting a lock
curl 127.0.0.1:9501
// Or
curl 127.0.0.1:9501/noMutex

// Test with file lock
curl 127.0.0.1:9501/flock

// Test Redis single-instance distributed lock
curl 127.0.0.1:9501/redisLock

// Test Redis Redlock
curl 127.0.0.1:9501/redLock

// Test coroutine-level mutex lock
curl 127.0.0.1:9501/channelLock

```
