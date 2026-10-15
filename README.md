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

## Managed recurring billing

Requires a Tinker server with the managed-billing, payment-method and payment-history
endpoints enabled. Installing the SDK does not deploy or enable those endpoints.
Existing plan-based subscription methods remain available.

Checkout uses exact integer minor-unit prices (for example, `15000` means EUR 150.00),
an explicit recurring interval (`month` or `year`), and a quantity. The SDK does not
convert prices or apply automatic discounts. Managed responses are unwrapped API
data objects/maps; payment history includes `items`, `snapshot`, `has_more` and
`next_cursor`.

| Method | Purpose |
|---|---|
| `checkout` | Create an exact-price recurring checkout |
| `billingResource` | Read a managed billing resource |
| `changeQuantity` | Change the current quantity |
| `scheduleQuantity` | Schedule a quantity change |
| `clearScheduledQuantity` | Remove a scheduled quantity change |
| `setCancelAtRenewal` | Set cancellation at renewal; pass false to clear it |
| `billingOperation` | Recover the durable result by idempotency key |
| `paymentMethodPortal` | Create a short-lived hosted payment-method link |
| `billingPaymentHistory` | Read one page of captured payments and adjustments |

```php
use Tinker\Model\DTO\ManagedCheckout;
use Tinker\Exception\ApiException;

$billing = $payments->subscriptions();
$billing->checkout(new ManagedCheckout(
    externalCustomerId: 'workspace-123', email: 'buyer@example.com', name: 'Buyer',
    currency: 'EUR', unitAmount: 15000, quantity: 3, interval: 'year',
    returnUrl: 'https://example.com/billing', idempotencyKey: 'checkout-123',
));
$billing->changeQuantity('resource-id', 4, 'quantity-123');
$billing->setCancelAtRenewal('resource-id', false, 'resume-123');
$billing->paymentMethodPortal('resource-id', 'https://example.com/billing', 'portal-123');
$page = $billing->billingPaymentHistory('resource-id', '', 0);
// After an uncertain command response, recover using the SAME key:
$operation = $billing->billingOperation('quantity-123');
// Catch ApiException and inspect $error->httpStatus, $error->outcome, etc.
```

Use integer minor-unit amounts and snapshots on a 64-bit PHP runtime. `ManagedCheckout`
is a request DTO with `toArray()` and an integer `unitAmount` field.

Use a stable, unique idempotency key for each mutation, and reuse it only with the
same payload. After a timeout, retrieve the durable result with the operation lookup
method. Missing or pending results do not prove a charge failed. Do not retry with a
new key to work around an uncertain result. Only an explicit `outcome == "not_applied"`
confirms no mutation; missing outcome remains uncertain. No automatic charge retry is
performed by these methods.

The payment-method portal updates payment details only. It cannot change quantity or
price, and paying an overdue invoice is a separate action through its hosted invoice
URL. Reuse the operation key after an uncertain response; use a new key to deliberately
open a fresh session after expiry. The server checks app ownership and requires a
managed subscription with a dedicated customer.

For payment history, start with an empty cursor and zero snapshot, then retain the
returned snapshot and pass `next_cursor` until `has_more` is false. Amounts are integer
minor units and timestamps are UTC RFC3339. The history includes captured cash payments,
tax-exclusive eligible amounts, successful refunds, and dispute principal withdrawals
and reinstatements (`reverses_id` links a reinstatement to its withdrawal).

Quarantine items with `reconciliation_reason`; commit a scan only after every page has
been verified. The snapshot is an event cutoff, not an immutable provider snapshot.
Rescan from the first page regularly and apply facts idempotently by payment/adjustment
ID. Zero-cash invoices are omitted from items while still advancing pagination. Currency
conversion and customer credits require explicit reconciliation; processing and dispute
fees are excluded from eligible principal. The SDK returns these facts without applying
credits or reconciling them automatically.

### Setup webhook acknowledgement

The dashboard sends a signed `setup.webhook_test` event with `source: "onboarding"`, `data.message`, and setup metadata. Parse it with the webhook handler, verify its signature using the application webhook secret, then acknowledge it with HTTP 200, 202, or 204. Setup events have onboarding data and do not convert to payment transactions. Event classification alone does not authenticate a request. Invalid signatures must be rejected; no payment lookup or fulfillment is needed for a valid setup test.

The signature uses HMAC-SHA256 over the JSON envelope excluding `security`, preserving envelope field order and Go JSON map ordering and escaping.

## Refunds, merchant payouts and checkout cancellation

A payout is a withdrawal from the merchant's available Tinker wallet balance to
an existing verified payout account. The app owner must enable withdrawal access
in the dashboard. SDKs cannot approve withdrawals or verify payout destinations.
Fees are deducted from the requested amount: requesting 100 with a fee of 2
reserves 100 and pays 98. Quote first and submit the displayed `fee_amount`.

```php
use Tinker\Model\DTO\RefundRequest;
use Tinker\Model\DTO\PayoutRequest;
use Tinker\Model\DTO\CancellationRequest;

$refund = $tinker->transactions()->refund($paymentId, new RefundRequest('requested_by_customer', 25), 'refund-order-42');
$history = $tinker->transactions()->refunds($paymentId);
$methods = $tinker->payouts()->methods();
$quote = $tinker->payouts()->quote($payoutMethodId, 100, 'KES');
$payout = $tinker->payouts()->requestPayout(new PayoutRequest($payoutMethodId, 100, 'KES', $quote['fee_amount']), 'withdrawal-42');
$status = $tinker->payouts()->get($payout->withdrawal->getId());
$cancellation = $tinker->transactions()->cancelCheckout($unpaidPaymentId, new CancellationRequest('Order cancelled'), 'cancel-order-42');
```

Keep the same idempotency key when retrying the same operation after a lost
response. A changed request with the same key is a conflict. Keys are required,
limited to 255 UTF-8 bytes, and never generated or rotated automatically.
Accepted refunds may be `pending_review`; payouts may be `pending_approval`.
Only `refunded`, `paid`, or provider-confirmed `cancelled` indicate completion.
Cancellation `outcome_unknown` requires querying/reconciliation, not blind retry.
Subscription cancellation is a separate existing operation.

Use `getRefund`, `cancellations`, `getCancellation`, and `payouts()->list()` to
track requests. `ApiException::$details` retains structured error details, such
as the recorded cancellation request ID for `CHECKOUT_CANCELLATION_UNSUPPORTED`
(422), or a new quote for `WITHDRAWAL_FEE_CHANGED` (409). Unsupported cancellation
never marks the payment cancelled. Existing administrative review screens handle
manual refunds and withdrawals.

Lifecycle wire tests use `tests/Fixtures/merchant-lifecycle.json`, copied from
`tinker-payments-openapi/fixtures/merchant-lifecycle.json`. Set
`TINKER_LIFECYCLE_FIXTURES` to test a different canonical fixture during coordinated
contract changes; update the checked-in copy with the same contract release.

Cancellation history returns the newest 100 requests and `has_more`; use the
single-request lookup to track a known older request. Signed
`checkout.cancellation.updated.v1` events contain a cancellation resource, not
a payment transaction. Verify signatures before acting on any event.
