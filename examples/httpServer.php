<?php
/**
 *
 *
 * Created by PhpStorm
 * User: Alex
 * Date: 2023-08-19 23:37
 */
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . '../vendor/autoload.php';
require_once './BankAccount.php';

use Swoole\Coroutine\Http\Server;
use function Swoole\Coroutine\run;

//  docker run -itd -p 6380:6379 redis
$redisHosts = [
    [
        'host' => '192.168.0.101',
        'port' => 6379
    ],
    [
        'host' => '192.168.0.101',
        'port' => 6380
    ],
    [
        'host' => '192.168.0.101',
        'port' => 6381
    ],
    [
        'host' => '192.168.0.101',
        'port' => 6382
    ],
    [
        'host' => '192.168.0.101',
        'port' => 6383
    ],
];

$redisInstances = array_map(function ($v) {
    $redis = new \Redis();
    $redis->connect($v['host'], $v['port']);
    return $redis;
}, $redisHosts);

run(function () use ($redisInstances) {
    $server = new Server('127.0.0.1', 9501, false);

    // curl 127.0.0.1:9501
    $server->handle('/', function ($request, $response) {
        (new BankAccount())->dispatch('noMutex');
        $response->end("Hello World\n");
    });

    // curl 127.0.0.1:9501/noMutex
    $server->handle('/noMutex', function ($request, $response) {
        (new BankAccount())->dispatch('noMutex');
        $response->end("Hello World\n");
    });

    // curl 127.0.0.1:9501/flock
    $server->handle('/flock', function ($request, $response) {
        (new BankAccount())->dispatch('flock');
        $response->end("Hello World\n");
    });

    // curl 127.0.0.1:9501/redisLock
    $server->handle('/redisLock', function ($request, $response) use ($redisInstances) {
        (new BankAccount())->dispatch('redisLock', $redisInstances);
        $response->end("Hello World\n");
    });

    // curl 127.0.0.1:9501/redLock
    $server->handle('/redLock', function ($request, $response) use ($redisInstances) {
        (new BankAccount())->dispatch('redLock', $redisInstances);
        $response->end("Hello World\n");
    });

    // curl 127.0.0.1:9501/channelLock
    $server->handle('/channelLock', function ($request, $response) {
        (new BankAccount())->dispatch('channelLock');
        $response->end("Hello World\n");
    });

    $server->handle('/stop', function ($request, $response) use ($server) {
        $response->end("Bye bye! See you again.\n");
        $server->shutdown();
    });

    $server->start();
});
