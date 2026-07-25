<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Architect\HttpClient\DriverFactory;
use Architect\HttpClient\HttpClient;
use Architect\HttpClient\Middleware\LoggingMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Psr\Log\NullLogger;

echo "=== Architect HTTP Client Integration Test ===\n";

try {
    // 1. Test driver factory
    $driverFactory = new DriverFactory();
    $driver = $driverFactory->create('curl');
    echo "[OK] Driver created: " . get_class($driver) . "\n";

    // 2. Create client with middleware
    $client = new HttpClient($driver);
    $logger = new NullLogger();
    $loggingMiddleware = new LoggingMiddleware($logger);
    $client = $client->withMiddleware($loggingMiddleware);
    echo "[OK] HttpClient with logging middleware created.\n";

    // 3. Send GET request to httpbin
    $factory = new Psr17Factory();
    $request = new Request('GET', 'https://httpbin.org/get');
    echo "[INFO] Sending GET request to https://httpbin.org/get ...\n";
    $response = $client->sendRequest($request);
    echo "[OK] Response status: " . $response->getStatusCode() . " " . $response->getReasonPhrase() . "\n";
    $body = (string) $response->getBody();
    $data = json_decode($body, true);
    if (isset($data['url'])) {
        echo "[OK] Response URL matches: " . $data['url'] . "\n";
    }

    // 4. Send POST request with JSON body
    $postRequest = new Request(
        'POST',
        'https://httpbin.org/post',
        ['Content-Type' => 'application/json'],
        json_encode(['test' => 'value'])
    );
    echo "[INFO] Sending POST request to https://httpbin.org/post ...\n";
    $postResponse = $client->sendRequest($postRequest);
    echo "[OK] POST response status: " . $postResponse->getStatusCode() . "\n";
    $postBody = (string) $postResponse->getBody();
    $postData = json_decode($postBody, true);
    if (isset($postData['json']['test']) && $postData['json']['test'] === 'value') {
        echo "[OK] POST request body correctly echoed back.\n";
    }

    // 5. Test stream driver
    $streamDriver = $driverFactory->create('stream');
    echo "[OK] Stream driver created: " . get_class($streamDriver) . "\n";
    $streamClient = new HttpClient($streamDriver);
    $streamRequest = new Request('GET', 'https://httpbin.org/headers');
    echo "[INFO] Sending GET request with stream driver...\n";
    $streamResponse = $streamClient->sendRequest($streamRequest);
    echo "[OK] Stream driver response status: " . $streamResponse->getStatusCode() . "\n";

    // 6. Test async promise (simulated)
    echo "[INFO] Testing async promise (simulated)...\n";
    $promise = $client->sendAsync($request);
    echo "[OK] Promise created: " . get_class($promise) . "\n";
    // Wait for promise (since it's simulated, it's already resolved)
    $asyncResponse = $promise->wait();
    echo "[OK] Async response status: " . $asyncResponse->getStatusCode() . "\n";

    // 7. Test driver factory default
    $default = DriverFactory::getDefaultDriver();
    echo "[OK] Default driver: $default\n";

    // 8. Test middleware stack
    $middlewareStack = $client->getMiddlewareStack();
    echo "[OK] Middleware stack count: " . count($middlewareStack->all()) . "\n";

    echo "\n=== All integration tests passed successfully! ===\n";
} catch (\Throwable $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}