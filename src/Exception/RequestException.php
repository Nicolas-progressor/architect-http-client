<?php

declare(strict_types=1);

namespace Architect\HttpClient\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Thrown when the request is invalid or cannot be sent.
 */
class RequestException extends HttpClientException implements RequestExceptionInterface
{
    private RequestInterface $request;

    public function __construct(
        string $message,
        RequestInterface $request,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}