<?php

declare(strict_types=1);

namespace Architect\HttpClient\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Thrown when the request cannot be completed due to network issues.
 */
class NetworkException extends HttpClientException implements NetworkExceptionInterface
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
