<?php

declare(strict_types=1);

namespace Architect\HttpClient\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for processing HTTP requests and responses.
 */
interface MiddlewareInterface
{
    /**
     * Process the request and return a response.
     *
     * @param RequestInterface $request
     * @param callable $next Next middleware/handler in the stack.
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}