<?php

declare(strict_types=1);

namespace Architect\HttpClient\Integration\Architect;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractServiceProvider;
use Architect\HttpClient\Contracts\HttpClientInterface;
use Architect\HttpClient\DriverFactory;
use Architect\HttpClient\HttpClient;

/**
 * Service provider for HTTP client integration with Architect Framework.
 */
class HttpClientServiceProvider extends AbstractServiceProvider
{
    /**
     * Register services.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void
    {
        $container->singleton('http.client.factory', function ($c) {
            return new DriverFactory();
        });

        $container->singleton('http.client', function ($c) {
            $config = $c->get('config')->get('http-client', []);
            $driverName = $config['default_driver'] ?? DriverFactory::getDefaultDriver();
            $driverOptions = $config['drivers'][$driverName]['options'] ?? [];
            $middlewareClasses = $config['middlewares'] ?? [];

            $factory = $c->get('http.client.factory');
            $driver = $factory->create($driverName, $driverOptions);

            $middlewares = [];
            foreach ($middlewareClasses as $middlewareClass) {
                if (class_exists($middlewareClass)) {
                    $middlewares[] = $c->make($middlewareClass);
                }
            }

            return new HttpClient($driver, $middlewares);
        });

        $container->alias(HttpClientInterface::class, 'http.client');
    }

    /**
     * Boot the service.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function boot(ContainerInterface $container): void
    {
        // Optionally publish configuration
    }

    /**
     * Get default configuration.
     *
     * @return array
     */
    public static function getDefaultConfig(): array
    {
        return [
            'default_driver' => DriverFactory::getDefaultDriver(),
            'drivers' => [
                'curl' => [
                    'class' => \Architect\HttpClient\Drivers\CurlDriver::class,
                    'options' => [
                        'timeout' => 30,
                        'verify_ssl' => true,
                        'follow_location' => true,
                        'max_redirects' => 10,
                        'user_agent' => 'Architect HttpClient',
                    ],
                ],
                'curl_multi' => [
                    'class' => \Architect\HttpClient\Drivers\CurlMultiDriver::class,
                    'options' => [
                        'timeout' => 30,
                        'verify_ssl' => true,
                        'follow_location' => true,
                        'max_redirects' => 10,
                        'user_agent' => 'Architect HttpClient (cURL Multi)',
                    ],
                ],
                'stream' => [
                    'class' => \Architect\HttpClient\Drivers\StreamDriver::class,
                    'options' => [
                        'timeout' => 30,
                        'follow_location' => true,
                        'max_redirects' => 5,
                        'user_agent' => 'Architect HttpClient (Stream)',
                    ],
                ],
            ],
            'middlewares' => [
                \Architect\HttpClient\Middleware\LoggingMiddleware::class,
            ],
            'options' => [
                'base_uri' => null,
                'headers' => [],
            ],
        ];
    }
}