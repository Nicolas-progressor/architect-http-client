<?php

declare(strict_types=1);

namespace Architect\HttpClient\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Promise for asynchronous HTTP requests.
 */
interface PromiseInterface
{
    /**
     * Attach callbacks for when the promise is fulfilled or rejected.
     *
     * @param callable $onFulfilled Called with ResponseInterface on success.
     * @param callable $onRejected Called with \Throwable on failure.
     * @return self
     */
    public function then(callable $onFulfilled, callable $onRejected): self;

    /**
     * Wait for the promise to complete and return the response.
     *
     * @return ResponseInterface
     * @throws \Throwable If the request fails.
     */
    public function wait(): ResponseInterface;

    /**
     * Check if the promise has been resolved.
     *
     * @return bool
     */
    public function isFulfilled(): bool;

    /**
     * Check if the promise has been rejected.
     *
     * @return bool
     */
    public function isRejected(): bool;

    /**
     * Get the result if fulfilled, or throw if rejected.
     *
     * @return ResponseInterface|null
     */
    public function getResult(): ?ResponseInterface;

    /**
     * Get the rejection reason if rejected.
     *
     * @return \Throwable|null
     */
    public function getReason(): ?\Throwable;
}
