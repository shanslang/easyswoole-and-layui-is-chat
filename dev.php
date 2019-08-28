<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-01
 * Time: 20:06
 */

return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9501,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SOCKET_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 4,
            'task_worker_num' => 4,
            'reload_async' => true,
            'task_enable_coroutine' => true,
            'max_wait_time'=>3,
            'package_max_length' => 1024*1024*5
        ],
    ],
    'TEMP_DIR' => null,
    'LOG_DIR' => null,
    'MYSQL'   => [
        'host'        => '192.168.23.130',
        'user'        => 'UserTest',
        'password'    => 'wNFnHbCrf37kw4dW',
        'database'    => 'UserTest',
        'port'        => '3306',
        'timeout'     => 3,
        'connect_timeout' => 5,
        'charset'         => 'utf8mb4',
    ],
   
    /*################ REDIS CONFIG ##################*/
    'REDIS'         => [
        'host'          => '127.0.0.1',
        'port'          => '6379',
        'auth'          => '11111',
        'POOL_MAX_NUM'  => '6',
        'POOL_TIME_OUT' => '0.1',
    ],
];
