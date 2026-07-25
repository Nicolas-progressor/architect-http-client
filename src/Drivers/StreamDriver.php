<?php

declare(strict_types=1);

namespace Architect\HttpClient\Drivers;

use Architect\HttpClient\Exception\NetworkException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Stream wrapper based HTTP driver (fallback when cURL is not available).
 */
class StreamDriver extends AbstractDriver
{
    protected function getDefaultOptions(): array
    {
        return [
            'timeout' => 30,
            'follow_location' => true,
            'max_redirects' => 5,
            'user_agent' => 'Architect HttpClient (Stream)',
            'ignore_errors' => true,
        ];
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        $data = $this->extractRequestData($request);
        $context = $this->createStreamContext($data);

        $stream = @fopen($data['url'], 'r', false, $context);
        if ($stream === false) {
            throw new NetworkException(
                'Failed to open stream for URL: ' . $data['url'],
                $request
            );
        }

        $metadata = stream_get_meta_data($stream);
        $headers = $metadata['wrapper_data'] ?? [];
        $body = stream_get_contents($stream);
        fclose($stream);

        $statusCode = $this->extractStatusCode($headers);
        $parsedHeaders = $this->parseHeaders($headers);

        return $this->createResponse($body, $statusCode, $parsedHeaders);
    }

    protected function createResponse(
        string $body,
        int $statusCode,
        array $headers
    ): ResponseInterface {
        // Reuse the same temporary response class as CurlDriver
        // In a real implementation, we would use a proper PSR-7 factory.
        return $this->createPsr7Response($body, $statusCode, $headers);
    }

    /**
     * Create stream context options.
     *
     * @param array $requestData
     * @return resource
     */
    private function createStreamContext(array $requestData)
    {
        $options = [
            'http' => [
                'method' => $requestData['method'],
                'header' => $this->formatHeaders($requestData['headers']),
                'content' => $requestData['body'],
                'timeout' => $this->options['timeout'],
                'follow_location' => $this->options['follow_location'] ? 1 : 0,
                'max_redirects' => $this->options['max_redirects'],
                'user_agent' => $this->options['user_agent'],
                'ignore_errors' => $this->options['ignore_errors'],
            ],
        ];

        return stream_context_create($options);
    }

    /**
     * Format headers for stream context.
     *
     * @param array $headers
     * @return string
     */
    private function formatHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = sprintf('%s: %s', $name, $value);
        }
        return implode("\r\n", $lines);
    }

    /**
     * Parse raw headers from stream metadata.
     *
     * @param array $rawHeaders
     * @return array
     */
    private function parseHeaders(array $rawHeaders): array
    {
        $headers = [];
        foreach ($rawHeaders as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * Extract HTTP status code from headers.
     *
     * @param array $headers
     * @return int
     */
    private function extractStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                return (int) $matches[1];
            }
        }
        return 200; // fallback
    }

    /**
     * Create a PSR-7 response (temporary implementation).
     *
     * @param string $body
     * @param int $statusCode
     * @param array $headers
     * @return ResponseInterface
     */
    private function createPsr7Response(
        string $body,
        int $statusCode,
        array $headers
    ): ResponseInterface {
        // Use the same anonymous class as CurlDriver for now.
        // In production, we should inject a PSR-7 response factory.
        return new class ($body, $statusCode, $headers) implements ResponseInterface {
            // ... same implementation as in CurlDriver
            // For brevity, we'll duplicate the code (should be refactored).
            private string $body;
            private int $statusCode;
            private array $headers;

            public function __construct(string $body, int $statusCode, array $headers)
            {
                $this->body = $body;
                $this->statusCode = $statusCode;
                $this->headers = $headers;
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }
            public function withProtocolVersion($version): self
            {
                return $this;
            }
            public function getHeaders(): array
            {
                return $this->headers;
            }
            public function hasHeader($name): bool
            {
                return isset($this->headers[$name]);
            }
            public function getHeader($name): array
            {
                return $this->headers[$name] ?? [];
            }
            public function getHeaderLine($name): string
            {
                return implode(', ', $this->getHeader($name));
            }
            public function withHeader($name, $value): self
            {
                $clone = clone $this;
                $clone->headers[$name] = (array) $value;
                return $clone;
            }
            public function withAddedHeader($name, $value): self
            {
                $clone = clone $this;
                $clone->headers[$name] = array_merge($clone->headers[$name] ?? [], (array) $value);
                return $clone;
            }
            public function withoutHeader($name): self
            {
                $clone = clone $this;
                unset($clone->headers[$name]);
                return $clone;
            }
            public function getBody(): \Psr\Http\Message\StreamInterface
            {
                return new class ($this->body) implements \Psr\Http\Message\StreamInterface {
                    private string $content;
                    private int $position = 0;
                    public function __construct(string $content)
                    {
                        $this->content = $content;
                    }
                    public function __toString(): string
                    {
                        return $this->content;
                    }
                    public function close(): void {}
                    public function detach()
                    {
                        return null;
                    }
                    public function getSize(): ?int
                    {
                        return strlen($this->content);
                    }
                    public function tell(): int
                    {
                        return $this->position;
                    }
                    public function eof(): bool
                    {
                        return $this->position >= strlen($this->content);
                    }
                    public function isSeekable(): bool
                    {
                        return true;
                    }
                    public function seek($offset, $whence = SEEK_SET): void
                    {
                        $this->position = $offset;
                    }
                    public function rewind(): void
                    {
                        $this->position = 0;
                    }
                    public function isWritable(): bool
                    {
                        return false;
                    }
                    public function write($string): int
                    {
                        return 0;
                    }
                    public function isReadable(): bool
                    {
                        return true;
                    }
                    public function read($length): string
                    {
                        $result = substr($this->content, $this->position, $length);
                        $this->position += strlen($result);
                        return $result;
                    }
                    public function getContents(): string
                    {
                        $result = substr($this->content, $this->position);
                        $this->position = strlen($this->content);
                        return $result;
                    }
                    public function getMetadata($key = null)
                    {
                        return null;
                    }
                };
            }
            public function withBody(\Psr\Http\Message\StreamInterface $body): self
            {
                $clone = clone $this;
                $clone->body = (string) $body;
                return $clone;
            }
            public function getStatusCode(): int
            {
                return $this->statusCode;
            }
            public function withStatus($code, $reasonPhrase = ''): self
            {
                $clone = clone $this;
                $clone->statusCode = $code;
                return $clone;
            }
            public function getReasonPhrase(): string
            {
                return '';
            }
        };
    }
}
