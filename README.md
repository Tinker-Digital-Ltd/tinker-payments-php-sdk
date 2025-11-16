# Tinker Payments PHP SDK

Official PHP SDK for [Tinker Payments API](https://payments.tinker.co.ke/docs).

## Installation

```bash
composer require tinker/payments
```

## Requirements

- PHP 8.1 or higher
- PSR-18 compatible HTTP client
- PSR-17 compatible HTTP factories

## Quick Start

```php
use Tinker\TinkerPayments;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

// Initialize the SDK
$tinker = new TinkerPayments(
    apiKey: 'your-api-key',
    httpClient: new Client(),
    requestFactory: new HttpFactory(),
    environment: 'production' // or 'sandbox' for testing
);

// Create a transaction
try {
    $transaction = $tinker->transactions()->create([
        'amount' => 1000.00,
        'currency' => 'KES',
        'customer' => [
            'email' => 'customer@example.com',
            'phone_number' => '+254700000000',
        ],
        'description' => 'Payment for order #123'
    ]);

    echo "Transaction created with ID: " . $transaction->id;
} catch (\Tinker\Exception\ApiException $e) {
    echo "API Error: " . $e->getMessage();
} catch (\Tinker\Exception\NetworkException $e) {
    echo "Network Error: " . $e->getMessage();
}

// Fetch a transaction
try {
    $transaction = $tinker->transactions()->fetch('transaction_id');
    if ($transaction->isSuccessful()) {
        echo "Transaction was successful!";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Documentation

For detailed API documentation, please visit [Tinker Payments API Documentation](https://payments.tinker.co.ke/docs).

## License

This SDK is released under the MIT License.