# Tinker Payments PHP SDK

Official PHP SDK for [Tinker Payments API](https://payments.tinker.co.ke/docs).

## Installation

```bash
composer require tinker/payments-php-sdk
```

## Requirements

- PHP 8.1 or higher
- PSR-18 compatible HTTP client
- PSR-17 compatible HTTP factories

## Configuration

The SDK supports using your own PSR-18 HTTP client and PSR-17 HTTP factories, or you can use the built-in cURL-based defaults.

### Using Default cURL Client

The SDK includes a built-in cURL-based HTTP client. Simply initialize without providing a client:

```php
use Tinker\TinkerPayments;

$tinker = new TinkerPayments(
    apiPublicKey: 'your-public-key',
    apiSecretKey: 'your-secret-key'
);
```

### Using Your Own PSR-18/PSR-17 Client

You can use any PSR-18 compatible HTTP client and PSR-17 compatible factories:

```php
use Tinker\TinkerPayments;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

$tinker = new TinkerPayments(
    apiPublicKey: 'your-public-key',
    apiSecretKey: 'your-secret-key',
    httpClient: new Client(),
    requestFactory: new HttpFactory()
);
```

**Note:** If you provide an HTTP client, you must also provide a request factory. Both are optional and will default to the built-in cURL implementation if not provided.

## Usage

### Initialize the SDK

```php
use Tinker\TinkerPayments;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

$tinker = new TinkerPayments(
    apiPublicKey: 'your-public-key',
    apiSecretKey: 'your-secret-key',
    httpClient: new Client(),
    requestFactory: new HttpFactory()
);
```

### Initiate a Payment

```php
use Tinker\TinkerPayments;
use Tinker\Enum\Gateway;
use Tinker\Model\DTO\InitiatePaymentRequest;

try {
    $initiateRequest = new InitiatePaymentRequest(
        amount: 100.00,
        currency: 'KES',
        gateway: Gateway::MPESA,
        merchantReference: 'ORDER-12345',
        callbackUrl: 'https://your-app.com/payment/return',
        customerPhone: '+254712345678',
        transactionDesc: 'Payment for order #12345',
        metadata: [
            'order_id' => '12345'
        ]
    );

    $transaction = $tinker->transactions()->initiate($initiateRequest);

    $initiationData = $transaction->getInitiationData();
    echo "Payment reference: " . $initiationData->paymentReference;
    if ($initiationData->authorizationUrl) {
        echo "Authorization URL: " . $initiationData->authorizationUrl;
    }
} catch (\Tinker\Exception\ApiException $e) {
    echo "API Error: " . $e->getMessage();
} catch (\Tinker\Exception\NetworkException $e) {
    echo "Network Error: " . $e->getMessage();
}
```

**Note:** The `callbackUrl` in the initiate payload is used as a return URL where users are redirected after completing the payment. This is different from webhook callbacks, which use the webhook URL configured in your app's webhook settings.

### Query a Transaction

```php
use Tinker\TinkerPayments;
use Tinker\Enum\Gateway;
use Tinker\Model\DTO\QueryPaymentRequest;

try {
    $queryRequest = new QueryPaymentRequest(
        paymentReference: 'TXN-abc123xyz',
        gateway: Gateway::MPESA,
    );

    $transaction = $tinker->transactions()->query($queryRequest);
    
    if ($transaction->isSuccessful()) {
        $queryData = $transaction->getQueryData();
        echo "Transaction ID: " . $queryData->id;
        echo "Amount: " . $queryData->amount . " " . $queryData->currency;
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Handle Webhook Callbacks

The platform will send POST callbacks to the webhook URL configured in your app's webhook settings (not the `callbackUrl` from the initiate payload). The webhook URL should be configured in your Tinker Payments dashboard.

```php
use Tinker\TinkerPayments;

try {
    $transaction = $tinker->webhooks()->handleFromRequest();
    
    if ($transaction->isSuccessful()) {
        $callbackData = $transaction->getCallbackData();
        echo "Payment successful: " . $callbackData->reference;
        echo "Amount: " . $callbackData->amount . " " . $callbackData->currency;
    } elseif ($transaction->isFailed()) {
        $callbackData = $transaction->getCallbackData();
        echo "Payment failed: " . $callbackData->reference;
    }
} catch (\Exception $e) {
    echo "Error processing webhook: " . $e->getMessage();
}
```

Or handle webhook with custom payload:

```php
use Tinker\TinkerPayments;

$webhookPayload = file_get_contents('php://input');
$transaction = $tinker->webhooks()->handle($webhookPayload);
```

## Documentation

For detailed API documentation, please visit [Tinker Payments API Documentation](https://payments.tinker.co.ke/docs).

## License

This SDK is released under the MIT License.