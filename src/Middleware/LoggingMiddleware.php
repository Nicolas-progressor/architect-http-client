<?php

declare(strict_types=1);

namespace Architect\HttpClient\Middleware;

use Architect\HttpClient\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs HTTP requests and responses.
 */
class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private string $logLevel;

    public function __construct(LoggerInterface $logger, string $logLevel = 'info')
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $start = microtime(true);

        $this->logRequest($request);

        try {
            $response = $next($request);
            $this->logResponse($response, $start);
            return $response;
        } catch (\Throwable $e) {
            $this->logException($e, $start);
            throw $e;
        }
    }

    private function logRequest(RequestInterface $request): void
    {
        $this->logger->log($this->logLevel, 'HTTP Request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
        ]);
    }

    private function logResponse(ResponseInterface $response, float $startTime): void
    {
        $duration = microtime(true) - $startTime;
        $this->logger->log($this->logLevel, 'HTTP Response', [
            'status' => $response->getStatusCode(),
            'duration' => $duration,
            'headers' => $response->getHeaders(),
        ]);
    }

    private function logException(\Throwable $e, float $startTime): void
    {
        $duration = microtime(true) - $startTime;
        $this->logger->error('HTTP Request Failed', [
            'exception' => $e->getMessage(),
            'duration' => $duration,
        ]);
    }
}
