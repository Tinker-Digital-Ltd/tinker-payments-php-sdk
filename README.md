# Tinker Payments PHP SDK

Official PHP SDK for [Tinker Payments API](https://payments.tinker.co.ke/docs).

## Installation

```bash
composer require tinker/payments-php-sdk
```

## Requirements

- PHP 8.1 or higher
- PSR-18 compatible HTTP client (optional, defaults to built-in cURL)
- PSR-17 compatible HTTP factories (optional, defaults to built-in cURL)

## Quick Start

```php
use Tinker\TinkerPayments;

$tinker = new TinkerPayments(
    apiPublicKey: 'your-public-key',
    apiSecretKey: 'your-secret-key'
);
```

## Usage

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
        returnUrl: 'https://your-app.com/payment/return',
        customerPhone: '+254712345678',
        transactionDesc: 'Payment for order #12345',
        metadata: ['order_id' => '12345']
    );

    $transaction = $tinker->transactions()->initiate($initiateRequest);
    $initiationData = $transaction->getInitiationData();
    
    if ($initiationData->authorizationUrl) {
        // Redirect user to authorization URL (Paystack, Stripe, etc.)
        header('Location: ' . $initiationData->authorizationUrl);
    }
} catch (\Tinker\Exception\ApiException $e) {
    echo "API Error: " . $e->getMessage();
} catch (\Tinker\Exception\NetworkException $e) {
    echo "Network Error: " . $e->getMessage();
}
```

**Note:** The `returnUrl` is where users are redirected after payment completion. Webhooks are configured separately in your dashboard.

### Query a Transaction

```php
use Tinker\Model\DTO\QueryPaymentRequest;

$queryRequest = new QueryPaymentRequest(
    paymentReference: 'TXN-abc123xyz',
    gateway: Gateway::MPESA
);

$transaction = $tinker->transactions()->query($queryRequest);

if ($transaction->isSuccessful()) {
    $queryData = $transaction->getQueryData();
    echo "Amount: " . $queryData->amount . " " . $queryData->currency;
}
```

### Handle Webhooks

Webhooks support multiple event types: payment, subscription, invoice, and settlement. Check the event type and handle accordingly:

```php
use Tinker\TinkerPayments;

$event = $tinker->webhooks()->handleFromRequest();

// Check event type
if ($event->isPaymentEvent()) {
    $paymentData = $event->getPaymentData();
    // Handle payment.completed, payment.failed, etc.
} elseif ($event->isSubscriptionEvent()) {
    $subscriptionData = $event->getSubscriptionData();
    // Handle subscription.created, subscription.cancelled, etc.
} elseif ($event->isInvoiceEvent()) {
    $invoiceData = $event->getInvoiceData();
    // Handle invoice.paid, invoice.failed
} elseif ($event->isSettlementEvent()) {
    $settlementData = $event->getSettlementData();
    // Handle settlement.processed
}

// Access event details
echo "Event type: " . $event->type;        // e.g., "payment.completed"
echo "Event source: " . $event->source;    // e.g., "payment"
echo "App ID: " . $event->meta->appId;
echo "Signature: " . $event->security->signature;
```

For payment events only, you can convert to a `Transaction` object:

```php
$transaction = $tinker->webhooks()->handleAsTransaction(file_get_contents('php://input'));
if ($transaction && $transaction->isSuccessful()) {
    $callbackData = $transaction->getCallbackData();
    echo "Payment successful: " . $callbackData->reference;
}
```

## Custom HTTP Client

You can use your own PSR-18/PSR-17 compatible client:

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

## Documentation

For detailed API documentation, visit [Tinker Payments API Documentation](https://payments.tinker.co.ke/docs).

## License

MIT License
