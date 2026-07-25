<?php

declare(strict_types=1);

namespace Architect\HttpClient;

use Architect\HttpClient\Contracts\DriverInterface;
use Architect\HttpClient\Contracts\HttpClientInterface;
use Architect\HttpClient\Contracts\MiddlewareInterface;
use Architect\HttpClient\Contracts\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Main HTTP client that uses a driver and middleware stack.
 */
class HttpClient implements HttpClientInterface
{
    private DriverInterface $driver;
    private MiddlewareStack $middlewareStack;
    private array $defaultOptions = [];

    public function __construct(DriverInterface $driver, array $middlewares = [])
    {
        $this->driver = $driver;
        $this->middlewareStack = new MiddlewareStack($middlewares);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Apply default options (e.g., base URI, headers) if any
        $request = $this->applyDefaultOptions($request);

        $handler = function (RequestInterface $request) {
            return $this->driver->send($request);
        };

        return $this->middlewareStack->handle($request, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync(RequestInterface $request): PromiseInterface
    {
        $request = $this->applyDefaultOptions($request);

        // Async request goes through middleware as well?
        // For simplicity, we'll just delegate to driver's async method.
        // In a more advanced implementation, middleware could support async.
        return $this->driver->sendAsync($request);
    }

    /**
     * {@inheritdoc}
     */
    public function withMiddleware(MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middlewareStack->push($middleware);
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutMiddleware(string $middlewareClass): self
    {
        $clone = clone $this;
        $clone->middlewareStack->remove($middlewareClass);
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->defaultOptions = array_merge($clone->defaultOptions, $options);
        return $clone;
    }

    /**
     * Get the underlying driver.
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Get the middleware stack.
     *
     * @return MiddlewareStack
     */
    public function getMiddlewareStack(): MiddlewareStack
    {
        return $this->middlewareStack;
    }

    /**
     * Apply default options to the request.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    private function applyDefaultOptions(RequestInterface $request): RequestInterface
    {
        // This is a placeholder; actual implementation would modify the request
        // based on options like base_uri, headers, etc.
        // For now, we just return the request unchanged.
        return $request;
    }
}