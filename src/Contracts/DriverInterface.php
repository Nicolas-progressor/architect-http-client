<?php

declare(strict_types=1);

namespace Architect\HttpClient\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Driver interface for low-level HTTP communication.
 */
interface DriverInterface
{
    /**
     * Send a synchronous HTTP request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Architect\HttpClient\Exception\HttpClientException
     */
    public function send(RequestInterface $request): ResponseInterface;

    /**
     * Send an asynchronous HTTP request.
     *
     * @param RequestInterface $request
     * @return PromiseInterface
     */
    public function sendAsync(RequestInterface $request): PromiseInterface;
}
