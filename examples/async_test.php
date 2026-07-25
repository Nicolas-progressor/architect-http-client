<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Architect\HttpClient\DriverFactory;
use Architect\HttpClient\HttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;

echo "=== Testing true async with CurlMultiDriver (improved) ===\n";

$factory = new Psr17Factory();
$driverFactory = new DriverFactory();

try {
    $driver = $driverFactory->create('curl_multi');
    echo "Driver created: " . get_class($driver) . "\n";

    $client = new HttpClient($driver);

    // URLs to fetch (httpbin endpoints with delay to see concurrency)
    $urls = [
        'https://httpbin.org/delay/1',
        'https://httpbin.org/delay/2',
        'https://httpbin.org/delay/1',
    ];

    $start = microtime(true);
    $promises = [];

    foreach ($urls as $i => $url) {
        $request = new Request('GET', $url);
        echo "Sending async request $i to $url\n";
        $promises[$i] = $client->sendAsync($request);
    }

    echo "All requests sent, waiting for parallel completion...\n";

    // Wait for all requests in parallel using driver's wait method
    $driver->wait();

    // Now all promises should be resolved, we can retrieve results
    foreach ($promises as $i => $promise) {
        $response = $promise->wait();
        echo "Response $i status: " . $response->getStatusCode() . "\n";
    }

    $end = microtime(true);
    $total = $end - $start;
    echo "Total time: " . round($total, 2) . " seconds\n";

    // If async works, total time should be less than sum of delays (1+2+1 = 4 seconds)
    // Because requests are made in parallel, expected ~2 seconds.
    if ($total < 3.0) {
        echo "SUCCESS: Async parallelism detected (total time $total s < 3 s).\n";
    } else {
        echo "WARNING: Async may not be parallel (total time $total s).\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}