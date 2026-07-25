<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default HTTP Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default HTTP driver that will be used by the
    | HTTP client. Supported drivers: "curl", "stream", "guzzle", "symfony".
    |
    */
    'default_driver' => \Architect\HttpClient\DriverFactory::getDefaultDriver(),

    /*
    |--------------------------------------------------------------------------
    | Driver Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each driver. The options will be
    | passed to the driver's constructor.
    |
    */
    'drivers' => [
        'curl' => [
            'class' => \Architect\HttpClient\Drivers\CurlDriver::class,
            'options' => [
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify_ssl' => true,
                'follow_location' => true,
                'max_redirects' => 10,
                'user_agent' => 'Architect HttpClient',
            ],
        ],
        'stream' => [
            'class' => \Architect\HttpClient\Drivers\StreamDriver::class,
            'options' => [
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 5,
                'user_agent' => 'Architect HttpClient (Stream)',
                'ignore_errors' => true,
            ],
        ],
        // 'guzzle' => [
        //     'class' => \Architect\HttpClient\Drivers\GuzzleDriver::class,
        //     'options' => [
        //         'timeout' => 30,
        //         'verify' => true,
        //     ],
        // ],
        // 'symfony' => [
        //     'class' => \Architect\HttpClient\Drivers\SymfonyDriver::class,
        //     'options' => [],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | List of middleware classes to apply to every request. Middleware will be
    | instantiated via the service container.
    |
    */
    'middlewares' => [
        \Architect\HttpClient\Middleware\LoggingMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Options
    |--------------------------------------------------------------------------
    |
    | Options that will be applied to every request, such as base URI, default
    | headers, etc.
    |
    */
    'options' => [
        'base_uri' => null,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ],
];