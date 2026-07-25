<?php

declare(strict_types=1);

namespace Architect\HttpClient\Exception;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * Base exception for HTTP client errors.
 */
class HttpClientException extends \RuntimeException implements ClientExceptionInterface {}
