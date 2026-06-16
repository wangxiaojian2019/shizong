<?php

return [
    'http'       => [
        'enable' => false, // 如果您主要使用WebSocket，HTTP服务可以关闭
    
    ],
    'server'     => [
        'host'      => '0.0.0.0', // 监听地址
        'port'      => 8080,      // WebSocket 监听端口，可自定义（如9502）
        'mode'      => SWOOLE_PROCESS, // 运行模式
        'sock_type' => SWOOLE_SOCK_TCP,
        'worker_num' => swoole_cpu_num(),
        'options'   => [
            // 所有 Swoole 服务器参数都应在此 'options' 数组内设置
            //'worker_num' => swoole_cpu_num(), // Worker 进程数:cite[4]:cite[7]
            'task_worker_num' => swoole_cpu_num(), // 异步任务进程数:cite[7]:cite[10]
            'reactor_num' => swoole_cpu_num(), // Reactor 线程数:cite[6]:cite[10]
            'pid_file' => runtime_path() . 'swoole.pid',
            'log_file' => runtime_path() . 'swoole.log',
            'daemonize' => false, // 开发环境建议 false，生产环境设为 true
            // ... 您的其他 options 配置
        ],
    ],
    
    'websocket'  => [
        'enable'        => true,
        'handler'       => \think\swoole\websocket\Handler::class,
        'ping_interval' => 25000,
        'ping_timeout'  => 60000,
        
        'room'          => [
            'type'  => 'table',
            'table' => [
                'room_rows'   => 8192,
                'room_size'   => 2048,
                'client_rows' => 4096,
                'client_size' => 2048,
            ],
            'redis' => [
                'host'          => '127.0.0.1',
                'port'          => 6379,
                'max_active'    => 3,
                'max_wait_time' => 5,
            ],
        ],
        'listen'        => [],
        'subscribe'     => [],
    ],
    'rpc'        => [
        'server' => [
            'enable' => false, // 如无需要，可保持关闭
            'host' => '0.0.0.0',
            'port' => 9000,
            'options' => [
                'worker_num' => swoole_cpu_num(), // 同样移至options内
            ],
            'services' => [],
        ],
        'client' => [],
    ],
    //队列
    'queue'      => [
        'enable'  => false,
        'workers' => [],
    ],
    'hot_update' => [
        'enable'  => env('APP_DEBUG', true),
        'name'    => ['*.php'],
        'include' => [app_path()],
        'exclude' => [],
    ],
    //连接池
    'pool'       => [
        'db'    => [
            'enable'        => true,
            'max_active'    => 3,
            'max_wait_time' => 5,
        ],
        'cache' => [
            'enable'        => true,
            'max_active'    => 3,
            'max_wait_time' => 5,
        ],
        //自定义连接池
    ],
    'ipc'        => [
        'type'  => 'unix_socket',
        'redis' => [
            'host'          => '127.0.0.1',
            'port'          => 6379,
            'max_active'    => 3,
            'max_wait_time' => 5,
        ],
    ],
    'tables'     => [],
    //每个worker里需要预加载以共用的实例
    'concretes'  => [],
    //重置器
    'resetters'  => [],
    //每次请求前需要清空的实例
    'instances'  => [],
    //每次请求前需要重新执行的服务
    'services'   => [],
];
