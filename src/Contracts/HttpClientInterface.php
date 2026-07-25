<?php

declare(strict_types=1);

namespace Architect\HttpClient\Contracts;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extended HTTP client interface with async support and middleware management.
 */
interface HttpClientInterface extends ClientInterface
{
    /**
     * Send an asynchronous HTTP request.
     *
     * @param RequestInterface $request
     * @return PromiseInterface
     */
    public function sendAsync(RequestInterface $request): PromiseInterface;

    /**
     * Add a middleware to the client.
     *
     * @param MiddlewareInterface $middleware
     * @return self Returns a new instance with the middleware added.
     */
    public function withMiddleware(MiddlewareInterface $middleware): self;

    /**
     * Remove a middleware by its class name.
     *
     * @param string $middlewareClass
     * @return self Returns a new instance without the middleware.
     */
    public function withoutMiddleware(string $middlewareClass): self;

    /**
     * Set default options for all requests.
     *
     * @param array $options
     * @return self
     */
    public function withOptions(array $options): self;
}