<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Architect\HttpClient\DriverFactory;
use Architect\HttpClient\HttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;

// This example assumes you have installed nyholm/psr7 for PSR-7 implementation.
// If not, you can use any other PSR-7 library.

$factory = new Psr17Factory();
$driverFactory = new DriverFactory();
$driver = $driverFactory->create('curl'); // or 'stream'

$client = new HttpClient($driver);

// Create a GET request
$request = new Request('GET', 'https://httpbin.org/get');
echo "Sending request to: " . $request->getUri() . "\n";

try {
    $response = $client->sendRequest($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Body:\n" . $response->getBody() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example with POST
$postRequest = new Request(
    'POST',
    'https://httpbin.org/post',
    ['Content-Type' => 'application/json'],
    json_encode(['foo' => 'bar'])
);

echo "\nSending POST request...\n";
try {
    $response = $client->sendRequest($postRequest);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Body:\n" . $response->getBody() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}