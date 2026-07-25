<?php

declare(strict_types=1);

namespace Architect\HttpClient\Drivers;

use Architect\HttpClient\Contracts\DriverInterface;
use Architect\HttpClient\Contracts\PromiseInterface;
use Architect\HttpClient\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Base driver with common functionality.
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Default options for the driver.
     *
     * @var array
     */
    protected array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * Get default driver options.
     *
     * @return array
     */
    abstract protected function getDefaultOptions(): array;

    /**
     * Create a response from raw HTTP data.
     *
     * @param string $body
     * @param int $statusCode
     * @param array $headers
     * @return ResponseInterface
     */
    abstract protected function createResponse(
        string $body,
        int $statusCode,
        array $headers
    ): ResponseInterface;

    /**
     * {@inheritdoc}
     */
    public function sendAsync(RequestInterface $request): PromiseInterface
    {
        // Default implementation: synchronous send wrapped in a resolved promise.
        $promise = new Promise();
        try {
            $response = $this->send($request);
            $promise->resolve($response);
        } catch (\Throwable $e) {
            $promise->reject($e);
        }
        return $promise;
    }

    /**
     * Helper to convert PSR-7 request to raw data for low-level transport.
     *
     * @param RequestInterface $request
     * @return array{method: string, url: string, headers: array, body: string}
     */
    protected function extractRequestData(RequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return [
            'method' => $request->getMethod(),
            'url' => (string) $request->getUri(),
            'headers' => $headers,
            'body' => (string) $request->getBody(),
        ];
    }
}