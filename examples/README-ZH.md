# 高并发场景下可能导致数据不一致示例

## 注意

由于此示例使用 swoole 扩展搭建 http 服务器，因此运行此示例前，**必须** 要有 swoole 扩展，并且版本需要 v4.4.0 或更高版本。[详见](https://wiki.swoole.com/#/coroutine/http_server?id=http%e6%9c%8d%e5%8a%a1%e5%99%a8)

## 使用

1. 修改 redis 连接配置（如果你需要测试基于 redis 实现的锁的话）

编辑 `httpServer.php` 文件，修改 $redisHosts 中的连接配置为你自己的 redis 配置信息。如果不需要测试，可以直接设置为 `$redisHosts = []`

2. 启动 http 服务器

```shell
php httpServer.php
```

3. 测试不同的锁

```shell

// 测试不设置锁的情况
curl 127.0.0.1:9501
// 或者
curl 127.0.0.1:9501/noMutex

// 测试加文件锁的情况
curl 127.0.0.1:9501/flock

// 测试 redis 单实例分布式锁
curl 127.0.0.1:9501/redisLock

// 测试 redis 红锁
curl 127.0.0.1:9501/redLock

// 测试协程级别的互斥锁
curl 127.0.0.1:9501/channelLock

```
