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
use Tinker\Enum\Gateway;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

// Initialize the SDK
$tinker = new TinkerPayments(
    apiPublicKey: 'your-public-key',
    apiSecretKey: 'your-secret-key',
    httpClient: new Client(),
    requestFactory: new HttpFactory()
);

// Initiate a payment
try {
    $transaction = $tinker->transactions()->initiate([
        'amount' => 100.00,
        'currency' => 'KES',
        'gateway' => Gateway::MPESA->value,
        'merchantReference' => 'ORDER-12345',
        'callbackUrl' => 'https://your-app.com/webhooks/payment',
        'customerPhone' => '+254712345678',
        'transactionDesc' => 'Payment for order #12345',
        'metadata' => [
            'order_id' => '12345'
        ]
    ]);

    echo "Transaction created with ID: " . $transaction->id;
} catch (\Tinker\Exception\ApiException $e) {
    echo "API Error: " . $e->getMessage();
} catch (\Tinker\Exception\NetworkException $e) {
    echo "Network Error: " . $e->getMessage();
}

// Query a transaction
try {
    $transaction = $tinker->transactions()->query('TXN-abc123xyz', Gateway::MPESA);
    if ($transaction->isSuccessful()) {
        echo "Transaction was successful!";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Handle webhook callbacks
try {
    $transaction = $tinker->webhooks()->handleFromRequest();
    
    if ($transaction->isSuccessful()) {
        echo "Payment successful: " . $transaction->reference;
    } elseif ($transaction->isFailed()) {
        echo "Payment failed: " . $transaction->reference;
    }
} catch (\Exception $e) {
    echo "Error processing webhook: " . $e->getMessage();
}

// Or handle webhook with custom payload
$webhookPayload = file_get_contents('php://input');
$transaction = $tinker->webhooks()->handle($webhookPayload);
```

## Documentation

For detailed API documentation, please visit [Tinker Payments API Documentation](https://payments.tinker.co.ke/docs).

## License

This SDK is released under the MIT License.