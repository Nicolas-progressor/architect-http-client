<?php

declare(strict_types=1);

namespace Architect\HttpClient;

use Architect\HttpClient\Contracts\DriverInterface;
use Architect\HttpClient\Drivers\CurlDriver;
use Architect\HttpClient\Drivers\CurlMultiDriver;
use Architect\HttpClient\Drivers\StreamDriver;
use Architect\HttpClient\Exception\HttpClientException;

/**
 * Factory for creating HTTP drivers based on configuration.
 */
class DriverFactory
{
    /**
     * Map of driver aliases to their class names.
     */
    private const DRIVER_MAP = [
        'curl' => CurlDriver::class,
        'curl_multi' => CurlMultiDriver::class,
        'stream' => StreamDriver::class,
        // 'guzzle' => GuzzleDriver::class,
        // 'symfony' => SymfonyDriver::class,
    ];

    /**
     * Create a driver instance.
     *
     * @param string $driverName
     * @param array $options
     * @return DriverInterface
     * @throws HttpClientException
     */
    public function create(string $driverName, array $options = []): DriverInterface
    {
        $driverClass = self::DRIVER_MAP[$driverName] ?? null;

        if ($driverClass === null) {
            throw new HttpClientException(
                sprintf('Unknown HTTP driver "%s". Available drivers: %s', 
                    $driverName, 
                    implode(', ', array_keys(self::DRIVER_MAP))
                )
            );
        }

        if (!class_exists($driverClass)) {
            throw new HttpClientException(
                sprintf('Driver class "%s" does not exist.', $driverClass)
            );
        }

        return new $driverClass($options);
    }

    /**
     * Get default driver based on environment (cURL preferred).
     *
     * @return string
     */
    public static function getDefaultDriver(): string
    {
        if (extension_loaded('curl')) {
            return 'curl';
        }

        // Fallback to stream wrapper
        return 'stream';
    }
}