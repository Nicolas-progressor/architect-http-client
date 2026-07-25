<?php

declare(strict_types=1);

namespace Architect\HttpClient\Drivers;

use Architect\HttpClient\Exception\NetworkException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * cURL-based HTTP driver.
 */
class CurlDriver extends AbstractDriver
{
    protected function getDefaultOptions(): array
    {
        return [
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify_ssl' => true,
            'follow_location' => true,
            'max_redirects' => 10,
            'user_agent' => 'Architect HttpClient (cURL)',
        ];
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL extension is not available.');
        }

        $ch = curl_init();
        $data = $this->extractRequestData($request);

        curl_setopt_array($ch, [
            CURLOPT_URL => $data['url'],
            CURLOPT_CUSTOMREQUEST => $data['method'],
            CURLOPT_HTTPHEADER => $this->formatHeaders($data['headers']),
            CURLOPT_POSTFIELDS => $data['body'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->options['timeout'],
            CURLOPT_CONNECTTIMEOUT => $this->options['connect_timeout'],
            CURLOPT_FOLLOWLOCATION => $this->options['follow_location'],
            CURLOPT_MAXREDIRS => $this->options['max_redirects'],
            CURLOPT_USERAGENT => $this->options['user_agent'],
            CURLOPT_SSL_VERIFYPEER => $this->options['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => $this->options['verify_ssl'] ? 2 : 0,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($errno !== 0) {
            throw new NetworkException(
                sprintf('cURL error %d: %s', $errno, $error),
                $request
            );
        }

        // Split headers and body
        $headerSize = $info['header_size'];
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return $this->createResponse(
            $body,
            (int) $info['http_code'],
            $this->parseHeaders($headers)
        );
    }

    protected function createResponse(
        string $body,
        int $statusCode,
        array $headers
    ): ResponseInterface {
        // We need a PSR-7 response implementation.
        // For now, we'll use a simple one from Architect's HTTP services if available.
        // Otherwise, we can create a basic class.
        // Let's assume we have a factory method.
        return $this->createPsr7Response($body, $statusCode, $headers);
    }

    /**
     * Convert associative headers to cURL format.
     *
     * @param array $headers
     * @return array
     */
    protected function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = sprintf('%s: %s', $name, $value);
        }
        return $formatted;
    }

    /**
     * Parse raw header string into associative array.
     *
     * @param string $headerString
     * @return array
     */
    protected function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($headerString));
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * Create a PSR-7 response.
     *
     * @param string $body
     * @param int $statusCode
     * @param array $headers
     * @return ResponseInterface
     */
    protected function createPsr7Response(
        string $body,
        int $statusCode,
        array $headers
    ): ResponseInterface {
        // We'll use the Architect's Response class if available.
        // For now, we'll create a simple implementation.
        // This is a temporary placeholder; should be replaced with proper PSR-7.
        return new class ($body, $statusCode, $headers) implements ResponseInterface {
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
                // Simple stream implementation
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
