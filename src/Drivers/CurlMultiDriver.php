<?php

declare(strict_types=1);

namespace Architect\HttpClient\Drivers;

use Architect\HttpClient\Exception\NetworkException;
use Architect\HttpClient\Exception\RequestException;
use Architect\HttpClient\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * cURL multi driver for asynchronous HTTP requests with true parallelism.
 */
class CurlMultiDriver extends CurlDriver
{
    /** @var resource|null */
    private $multiHandle = null;

    /** @var array<int, array{request: RequestInterface, promise: Promise, handle: resource}> */
    private $activeRequests = [];

    /** @var bool */
    private $running = false;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('cURL extension is not available.');
        }
        $this->multiHandle = curl_multi_init();
        if ($this->multiHandle === false) {
            throw new \RuntimeException('Failed to initialize cURL multi handle.');
        }
    }

    public function __destruct()
    {
        if ($this->multiHandle !== null) {
            // Clean up any remaining handles
            foreach ($this->activeRequests as $id => $data) {
                curl_multi_remove_handle($this->multiHandle, $data['handle']);
                curl_close($data['handle']);
                $data['promise']->reject(new NetworkException('Request cancelled due to driver destruction.', $data['request']));
            }
            curl_multi_close($this->multiHandle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync(RequestInterface $request): Promise
    {
        $promise = new Promise();
        $handle = $this->createCurlHandle($request);
        $id = (int) $handle;

        $this->activeRequests[$id] = [
            'request' => $request,
            'promise' => $promise,
            'handle' => $handle,
        ];

        curl_multi_add_handle($this->multiHandle, $handle);
        // Start execution if not already running
        $this->tick();

        return $promise;
    }

    /**
     * Perform a single tick of the multi handle, processing any completed requests.
     */
    public function tick(): void
    {
        if (empty($this->activeRequests)) {
            return;
        }

        // Perform non-blocking execution
        do {
            $status = curl_multi_exec($this->multiHandle, $stillRunning);
            $this->running = $stillRunning;
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        if ($status !== CURLM_OK) {
            // Handle error
            return;
        }

        // Check for completed handles
        while ($info = curl_multi_info_read($this->multiHandle)) {
            $handle = $info['handle'];
            $id = (int) $handle;
            if (!isset($this->activeRequests[$id])) {
                continue;
            }

            $data = $this->activeRequests[$id];
            $request = $data['request'];
            $promise = $data['promise'];

            if ($info['result'] === CURLE_OK) {
                $response = $this->createResponseFromHandle($handle, $request);
                $promise->resolve($response);
            } else {
                $error = curl_error($handle);
                $exception = new NetworkException(
                    sprintf('cURL error %d: %s', $info['result'], $error),
                    $request
                );
                $promise->reject($exception);
            }

            curl_multi_remove_handle($this->multiHandle, $handle);
            curl_close($handle);
            unset($this->activeRequests[$id]);
        }
    }

    /**
     * Wait for all pending async requests to complete.
     */
    public function wait(): void
    {
        while (!empty($this->activeRequests)) {
            $this->tick();
            // If still running, wait a short time to avoid busy looping
            if ($this->running) {
                // Use curl_multi_select to block until activity
                $select = curl_multi_select($this->multiHandle, 0.01);
                if ($select === -1) {
                    usleep(1000);
                }
            }
        }
    }

    /**
     * Create a cURL handle for a request (without executing).
     */
    private function createCurlHandle(RequestInterface $request)
    {
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

        return $ch;
    }

    /**
     * Create a response from a completed cURL handle.
     */
    private function createResponseFromHandle($handle, RequestInterface $request): ResponseInterface
    {
        $response = curl_multi_getcontent($handle);
        $info = curl_getinfo($handle);

        $headerSize = $info['header_size'];
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return $this->createResponse(
            $body,
            (int) $info['http_code'],
            $this->parseHeaders($headers)
        );
    }
}