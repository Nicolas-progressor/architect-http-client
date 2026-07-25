<?php

declare(strict_types=1);

namespace Architect\HttpClient;

use Architect\HttpClient\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Manages a stack of middleware and executes them in order.
 */
class MiddlewareStack
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    public function __construct(array $middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            $this->push($middleware);
        }
    }

    /**
     * Add a middleware to the stack.
     *
     * @param MiddlewareInterface $middleware
     * @return void
     */
    public function push(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Remove a middleware by its class name.
     *
     * @param string $className
     * @return void
     */
    public function remove(string $className): void
    {
        $this->middlewares = array_filter(
            $this->middlewares,
            fn ($middleware) => !$middleware instanceof $className
        );
    }

    /**
     * Handle the request through the middleware stack.
     *
     * @param RequestInterface $request
     * @param callable $handler Final driver callable.
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, callable $handler): ResponseInterface
    {
        $next = $handler;

        // Build the chain from the last middleware to the first
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function (RequestInterface $request) use ($middleware, $next) {
                return $middleware->process($request, $next);
            };
        }

        return $next($request);
    }

    /**
     * Get all middlewares.
     *
     * @return MiddlewareInterface[]
     */
    public function all(): array
    {
        return $this->middlewares;
    }

    /**
     * Check if the stack is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->middlewares);
    }
}