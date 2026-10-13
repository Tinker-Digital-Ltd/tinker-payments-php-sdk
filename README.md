# Tinker Payments PHP SDK

Official PHP SDK for Tinker Payments API.

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

The SDK auto-selects API environment by key prefix:
- `pk_test_` / `sk_test_` -> sandbox (`https://sandbox-api.tinkerpayments.com/v1`)
- `pk_live_` / `sk_live_` -> production (`https://api.tinkerpayments.com/v1`)

You can also override the API base URL explicitly:

```php
$tinker = new TinkerPayments(
    apiPublicKey: 'pk_test_xxx',
    apiSecretKey: 'sk_test_xxx',
    baseUrl: 'http://localhost:8080/v1' // or http://localhost:8080
);
```

## Usage

All standardized API responses follow:

```json
{
  "success": true,
  "data": {},
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-02-11T22:52:45Z",
    "environment": "sandbox"
  }
}
```

The SDK returns the `data` payload directly and exposes the latest `meta` via:
- `transactions()->getLastMeta()`
- `subscriptions()->getLastMeta()`
- `getLastAuthMeta()`

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

// Standard response metadata from backend
$meta = $tinker->transactions()->getLastMeta();
echo $meta?->requestId;   // e.g. UUID request_id
echo $meta?->environment; // sandbox | production
```

### Manage Merchant Subscriptions

```php
use Tinker\Model\DTO\CreateSubscriptionPlanRequest;
use Tinker\Model\DTO\CreateSubscriptionRequest;
use Tinker\Model\DTO\SubscriptionCustomer;

// 1) Create a plan
$plan = $tinker->subscriptions()->createPlan(
    new CreateSubscriptionPlanRequest(
        name: 'Pro Monthly',
        amount: 29.99,
        currency: 'USD',
        intervals: ['monthly']
    )
);

// 2) Create a customer subscription
$subscription = $tinker->subscriptions()->create(
    new CreateSubscriptionRequest(
        planId: $plan['id'],
        gateway: 'stripe',
        billingPeriod: 'monthly',
        customer: new SubscriptionCustomer(
            externalCustomerId: 'cust_001',
            name: 'Jane Doe',
            email: 'jane@example.com'
        )
    )
);

// 3) List and cancel
$allSubscriptions = $tinker->subscriptions()->list();
$tinker->subscriptions()->cancel($subscription['subscription_id']);

// Metadata is available here too
$meta = $tinker->subscriptions()->getLastMeta();
```

### Handle Webhooks

Webhooks support multiple event types: payment, subscription, invoice, and settlement. Check the event type and handle accordingly:

```php
use Tinker\TinkerPayments;

$event = $tinker->webhooks()->handleFromRequest();

if (!$tinker->webhooks()->verifySignature($event, 'your-webhook-secret')) {
    http_response_code(401);
    exit('Invalid signature');
}

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

Authentication metadata is also available after token fetch:

```php
$tinker->transactions()->query($queryRequest); // triggers auth if token not cached
$authMeta = $tinker->getLastAuthMeta();
echo $authMeta?->requestId;
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

For detailed API documentation, use your Tinker Payments frontend docs route (`/docs`).

## License

MIT License

## API errors

API exceptions preserve `httpStatus`, `errorCode`, `providerCode`, `requestId`, `retryAfter`, and `outcome`.
Request IDs prefer `error.request_id` over the `X-Request-ID` header. `Retry-After`
is retained as its original header string. Non-JSON HTTP failures retain status and
headers as API errors with an unknown outcome. Existing exception constructors and
legacy code/message access remain compatible.

Only an explicit `not_applied` outcome confirms no mutation. A missing outcome
remains uncertain.
