<?php

declare(strict_types=1);

namespace Architect\HttpClient;

use Architect\HttpClient\Contracts\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Simple promise implementation for asynchronous HTTP requests.
 */
class Promise implements PromiseInterface
{
    private ?ResponseInterface $result = null;
    private ?\Throwable $reason = null;
    private bool $fulfilled = false;
    private bool $rejected = false;
    private array $onFulfilledCallbacks = [];
    private array $onRejectedCallbacks = [];

    /**
     * Create a pending promise.
     */
    public function __construct() {}

    /**
     * Resolve the promise with a successful response.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function resolve(ResponseInterface $response): void
    {
        if ($this->fulfilled || $this->rejected) {
            return;
        }

        $this->result = $response;
        $this->fulfilled = true;

        foreach ($this->onFulfilledCallbacks as $callback) {
            $callback($response);
        }

        $this->clearCallbacks();
    }

    /**
     * Reject the promise with an error.
     *
     * @param \Throwable $reason
     * @return void
     */
    public function reject(\Throwable $reason): void
    {
        if ($this->fulfilled || $this->rejected) {
            return;
        }

        $this->reason = $reason;
        $this->rejected = true;

        foreach ($this->onRejectedCallbacks as $callback) {
            $callback($reason);
        }

        $this->clearCallbacks();
    }

    public function then(callable $onFulfilled, callable $onRejected): self
    {
        if ($this->fulfilled) {
            $onFulfilled($this->result);
        } elseif ($this->rejected) {
            $onRejected($this->reason);
        } else {
            $this->onFulfilledCallbacks[] = $onFulfilled;
            $this->onRejectedCallbacks[] = $onRejected;
        }

        return $this;
    }

    public function wait(): ResponseInterface
    {
        if ($this->fulfilled) {
            return $this->result;
        }

        if ($this->rejected) {
            throw $this->reason;
        }

        // In a real implementation, we would wait for the async operation to complete.
        // For now, we'll just throw an exception (should be overridden by drivers).
        throw new \RuntimeException('Promise not resolved yet. Use async driver.');
    }

    public function isFulfilled(): bool
    {
        return $this->fulfilled;
    }

    public function isRejected(): bool
    {
        return $this->rejected;
    }

    public function getResult(): ?ResponseInterface
    {
        return $this->result;
    }

    public function getReason(): ?\Throwable
    {
        return $this->reason;
    }

    private function clearCallbacks(): void
    {
        $this->onFulfilledCallbacks = [];
        $this->onRejectedCallbacks = [];
    }
}
