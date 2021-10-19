<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
     */

    'default'     => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
     */

    'connections' => [

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'   => '',
        ],

        'mysql'  => [
            'driver'      => 'mysql',
            'read'        => [
                'host'     => env('DB_HOST_READ', '127.0.0.1'),
                'username' => env('DB_USERNAME_READ', 'read'),
                'password' => env('DB_PASSWORD_READ', ''),
            ],
            'write'       => [
                'host'     => env('DB_HOST_WRITE', '127.0.0.1'),
                'username' => env('DB_USERNAME_WRITE', 'write'),
                'password' => env('DB_PASSWORD_WRITE', ''),
            ],
            'port'        => env('DB_PORT', '3306'),
            'database'    => env('DB_DATABASE', 'c01'),
            // 'username' => env('DB_USERNAME', 'root'),
            // 'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'modes'       => [
                // 'ONLY_FULL_GROUP_BY',
                // 'STRICT_TRANS_TABLES',
                // 'NO_ZERO_IN_DATE',
                // 'NO_ZERO_DATE',
                // 'ERROR_FOR_DIVISION_BY_ZERO',
                // 'NO_AUTO_CREATE_USER',
                // 'NO_ENGINE_SUBSTITUTION',
            ],
            'engine'      => 'InnoDB',
        ],

        'write'  => [
            'driver'      => 'mysql',
            'host'        => env('DB_HOST_WRITE', '127.0.0.1'),
            'username'    => env('DB_USERNAME_WRITE', 'write'),
            'password'    => env('DB_PASSWORD_WRITE', ''),
            'port'        => env('DB_PORT', '3306'),
            'database'    => env('DB_DATABASE', 'c01'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'modes'       => [
                // 'ONLY_FULL_GROUP_BY',
                // 'STRICT_TRANS_TABLES',
                // 'NO_ZERO_IN_DATE',
                // 'NO_ZERO_DATE',
                // 'ERROR_FOR_DIVISION_BY_ZERO',
                // 'NO_AUTO_CREATE_USER',
                // 'NO_ENGINE_SUBSTITUTION',
            ],
            'engine'      => 'InnoDB',

        ],

        'log'    => [
            'driver'      => 'mysql',
            'read'        => [
                'host'     => env('DB_HOST_READ', '127.0.0.1'),
                'username' => env('DB_USERNAME_READ', 'read'),
                'password' => env('DB_PASSWORD_READ', ''),
            ],
            'write'       => [
                'host'     => env('DB_HOST_WRITE', '127.0.0.1'),
                'username' => env('DB_USERNAME_WRITE', 'write'),
                'password' => env('DB_PASSWORD_WRITE', ''),
            ],
            'port'        => env('DB_PORT', '3306'),
            'database'    => env('DB_DATABASE_LOG', 'c01_log'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8',
            'collation'   => 'utf8_general_ci',
            'prefix'      => '',
            'strict'      => true,
            'modes'       => [
                // 'ONLY_FULL_GROUP_BY',
                // 'STRICT_TRANS_TABLES',
                // 'NO_ZERO_IN_DATE',
                // 'NO_ZERO_DATE',
                // 'ERROR_FOR_DIVISION_BY_ZERO',
                // 'NO_AUTO_CREATE_USER',
                // 'NO_ENGINE_SUBSTITUTION',
            ],
            'engine'      => 'InnoDB',
        ],

        'write_log'  => [
            'driver'      => 'mysql',
            'host'        => env('DB_HOST_WRITE', '127.0.0.1'),
            'username'    => env('DB_USERNAME_WRITE', 'write'),
            'password'    => env('DB_PASSWORD_WRITE', ''),
            'port'        => env('DB_PORT', '3306'),
            'database'    => env('DB_DATABASE_LOG', 'c01_log'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => '',
            'strict'      => true,
            'modes'       => [
                // 'ONLY_FULL_GROUP_BY',
                // 'STRICT_TRANS_TABLES',
                // 'NO_ZERO_IN_DATE',
                // 'NO_ZERO_DATE',
                // 'ERROR_FOR_DIVISION_BY_ZERO',
                // 'NO_AUTO_CREATE_USER',
                // 'NO_ENGINE_SUBSTITUTION',
            ],
            'engine'      => 'InnoDB',

        ],

        'old'    => [
            'driver'      => 'mysql',
            'host'        => '127.0.0.1',
            'username'    => 'root',
            'password'    => '',
            'port'        => '3306',
            'database'    => 'old',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset'     => 'utf8',
            'collation'   => 'utf8_general_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => null,
        ],

        'pgsql'  => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'sslmode'  => 'prefer',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
     */

    'migrations'  => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
     */

    'redis'       => [

        'client'   => 'phpredis',

        'root'     => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
        ],

        'default'  => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
            'prefix'   => implode(':', [
                env('REDIS_PREFIX', 'laravel'),
                env('REDIS_PREFIX_DEFAULT', 'web-stie'),
                '',
            ]),
        ],

        'member'   => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
            'prefix'   => implode(':', [
                env('REDIS_PREFIX', 'laravel'),
                env('REDIS_PREFIX_MEMBER', 'web-stie'),
                '',
            ]),
        ],

        'session'  => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('SESSION_DATABASE', 0),
            'prefix'   => implode(':', [
                env('REDIS_PREFIX', 'laravel'),
                env('SESSION_PREFIX', 'session'),
                '',
            ]),
        ],
        // 暫存玩家開啟遊戲時的 access token 等相關資料
        'provider' => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('PROVIDER_REDIS_DATABASE', 0),
            'prefix'   => implode(':', [
                env('REDIS_PREFIX', 'laravel'),
                env('PROVIDER_REDIS_PREFIX', 'session'),
                '',
            ]),
        ],

        'cache'    => [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
            'prefix'   => implode(':', [
                env('REDIS_PREFIX', 'laravel'),
                'cache',
                '',
            ]),
        ],
    ],

];