# Tinker Payments PHP SDK

PHP 8.1+ SDK with automatic authentication and a built-in cURL transport. Keep secret keys on the server.

## Install and configure

```sh
composer require tinker/payments-php-sdk
```

```php
require 'vendor/autoload.php';
use Tinker\TinkerPayments;

$tinker = new TinkerPayments('pk_test_xxx', 'sk_test_xxx');
```

Test key prefixes select `https://sandbox-api.tinkerpayments.com/v1/`; other keys select `https://api.tinkerpayments.com/v1/`. Set the `baseUrl:` constructor argument to override it (a host with or without `/v1`). Supply `httpClient:` and `requestFactory:` for a PSR-18 client and PSR-17 factory, such as Guzzle's `Client` and `HttpFactory`.

## Payments

```php
use Tinker\Enum\Gateway;
use Tinker\Model\DTO\InitiatePaymentRequest;
use Tinker\Model\DTO\QueryPaymentRequest;

$payment = $tinker->transactions()->initiate(new InitiatePaymentRequest(
    amount: 100, currency: 'KES', gateway: Gateway::MPESA,
    merchantReference: 'ORDER-12345',
    returnUrl: 'https://merchant.example/payment/return',
    customerPhone: '+254712345678',
));
$result = $tinker->transactions()->query(new QueryPaymentRequest(
    paymentReference: 'TXN-abc123xyz', gateway: Gateway::MPESA,
));
```

Payments use currency units. Mpesa requires `customerPhone`; `returnUrl` is the browser destination, while webhooks are configured separately in the dashboard. Hosted providers may return an authorization URL through `$payment->getInitiationData()?->authorizationUrl`.

## Plan subscriptions

```php
use Tinker\Model\DTO\CreateSubscriptionPlanRequest;
use Tinker\Model\DTO\CreateSubscriptionRequest;
use Tinker\Model\DTO\SubscriptionCustomer;

$plans = $tinker->subscriptions();
$plan = $plans->createPlan(new CreateSubscriptionPlanRequest(
    name: 'Pro Monthly', amount: 29.99, currency: 'USD', intervals: ['monthly'],
));
$subscription = $plans->create(new CreateSubscriptionRequest(
    planId: $plan['id'], gateway: 'stripe', billingPeriod: 'monthly',
    customer: new SubscriptionCustomer(
        externalCustomerId: 'cust_001', name: 'Jane Doe', email: 'jane@example.com',
    ),
));
$all = $plans->list();
$plans->cancel($subscription['subscription_id']);
```

## Managed recurring billing

Checkout uses exact integer minor-unit prices (`15000` = EUR 150.00), a `month` or `year` interval, and a quantity. The SDK does not convert prices or apply discounts. Responses are unwrapped data maps/objects.

| Method | Purpose |
|---|---|
| `checkout` | Create an exact-price recurring checkout |
| `expireCheckout` | Expire an abandoned, unpaid checkout |
| `billingResource` | Read a managed billing resource |
| `changeQuantity` | Change the current quantity |
| `scheduleQuantity` | Schedule a quantity change |
| `clearScheduledQuantity` | Remove a scheduled quantity change |
| `setCancelAtRenewal` | Set cancellation at renewal; pass false to clear it |
| `setTrialEnd` | Move the next charge to a given time |
| `endTrial` | End a trial now and charge the full period |
| `billingOperation` | Recover the durable result by idempotency key |
| `paymentMethodPortal` | Create a short-lived hosted payment-method link |
| `billingPaymentHistory` | Read one page of captured payments and adjustments |

```php
use Tinker\Model\DTO\ManagedCheckout;

$billing = $tinker->subscriptions();
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
```

Use integer minor-unit amounts and snapshots on a 64-bit PHP runtime. `ManagedCheckout`
is a request DTO with `toArray()` and an integer `unitAmount` field.

### Expiring an abandoned checkout

An abandoned checkout link can still be completed later and create a second subscription. Expire it to close the link.

```php
$billing->expireCheckout('checkout-id', 'expire-123');
```

- `open` checkout: becomes `expired` (`managed_state.status` is `canceled`) and the session is returned.
- Already `expired`: the session is returned unchanged, so retrying is safe.
- `complete`, including completion while the call is in flight: HTTP 409 `CHECKOUT_COMPLETED` with `outcome: not_applied`. Read the checkout with `billingResource` and use its subscription.

A non-checkout ID (`sub_...`) returns 400 `INVALID_ID`. An expired checkout sends no `subscription.cancelled` webhook.

### Trials with a required payment method

Set `trialDays` (1 to 730) to collect a card at checkout and charge first when the trial ends. The SDK sends `trial_days` only when it is above 0, so checkouts without a trial keep working against servers released before trial support. A gateway without trial support returns HTTP 422 `TRIAL_UNSUPPORTED` with `outcome: not_applied`. Limiting a customer to one trial is the app's responsibility. Alternatively set `trialEnd` to an exact end 48 hours to 730 days ahead; it cannot be combined with `trialDays` (HTTP 400), and the SDK sends it only when set.

```php
$billing->checkout(new ManagedCheckout(
    externalCustomerId: 'workspace-123', email: 'buyer@example.com', name: 'Buyer',
    currency: 'EUR', unitAmount: 15000, quantity: 3, interval: 'year',
    returnUrl: 'https://example.com/billing', idempotencyKey: 'checkout-124',
    trialDays: 14,
));
```

### Changing the trial end

```php
$billing->setTrialEnd('resource-id', new DateTimeImmutable('+30 days'), 'trial-end-123');
$billing->endTrial('resource-id', 'end-trial-123');
```

`setTrialEnd` moves the next charge to a given time 1 hour to 730 days ahead. On a trialing subscription it moves the trial end earlier or later; on a paid (`active`) subscription only to a time on or after `current_period_end`, which makes it `trialing` without charging. Scheduled seat changes must be cleared first. `endTrial` ends a trial now and charges the full period immediately; if the charge fails the subscription becomes `past_due`. Both return the subscription with `managed_state`; state-dependent rejections arrive as 502 `BILLING_GATEWAY_ERROR` with `outcome: not_applied`. Ending a trial early sends `invoice.paid` and `payment.updated` (not `subscription.renewed`); extending a paid subscription produces a €0 `invoice.paid`.

Checkout and billing resource responses include a provider-neutral `managed_state` map: `status` (`pending`, `trialing`, `active`, `past_due`, `paused` or `canceled`), `trial_ends_at`, `current_period_end` and `cancel_at_period_end`. Read it instead of raw provider fields; grant access while `status` is `trialing` or `active` and `current_period_end` is in the future. Managed subscription webhooks carry `billing_status` and `trial_ends_at`, and `subscription.trial_will_end` arrives up to three days before the trial ends. The trial's €0 invoice sends `invoice.paid` with amount 0 and is not a payment.

Use a stable key per mutation and reuse it with the same payload after a lost response. Recover through the operation lookup; missing or pending results remain uncertain. Only an explicit `outcome` of `not_applied` confirms no mutation.

The payment-method portal changes payment details only; quantity, price and overdue invoice payment are separate actions. It requires an app-owned managed subscription with a dedicated customer. Reuse the operation key after a lost response and use a new key for a deliberately new session after expiry.

For payment history, keep the returned `snapshot` across `next_cursor` pages until `has_more` is false. Amounts are integer minor units and timestamps are UTC RFC3339. History covers captured cash, tax-exclusive eligible amounts, refunds, and dispute principal withdrawals/reinstatements (`reverses_id` identifies the reversed withdrawal).

Quarantine items with `reconciliation_reason` and verify every page before committing a scan. The snapshot is an event cutoff, not an immutable provider snapshot: rescan from the first page to discover later adjustments, then apply facts idempotently by payment/adjustment ID. Zero-cash invoices advance pagination without an item. Processing/dispute fees are excluded from eligible principal; currency conversion and customer credits need explicit reconciliation.

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
$destinations = array_filter($tinker->payouts()->methods(),
    fn ($method) => $method['is_verified'] && $method['status'] === 'active');
$destination = reset($destinations);
if (!$destination) { throw new \RuntimeException('Configure an active, verified payout method'); }
$payoutMethodId = $destination['id'];
$quote = $tinker->payouts()->quote($payoutMethodId, 100, $destination['currency']);
$payout = $tinker->payouts()->requestPayout(new PayoutRequest($payoutMethodId, 100, $destination['currency'], $quote['fee_amount']), 'withdrawal-42');
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

Cancellation history returns the newest 100 requests and `has_more`; use the
single-request lookup to track a known older request. A cancellation with
`payment_conflict` means later successful collection conflicts with cancellation
evidence; investigate the successful payment and use a refund if needed.

## Webhooks

```php
$event = $tinker->webhooks()->handleFromRequest();
if (!$tinker->webhooks()->verifySignature($event, $webhookSecret)) {
    http_response_code(401);
    exit('Invalid signature');
}
$transaction = $event->toTransaction(); // null for setup/lifecycle events
```

Verify the signature before consuming fields or converting the event to a transaction; reuse the parsed event rather than reading the request body twice. Deduplicate by event ID. The SDK verifies HMAC-SHA256 over the envelope excluding `security`, using the server's field order, map ordering and escaping.

After verification, acknowledge `setup.webhook_test` (`source: onboarding`) with HTTP 200, 202 or 204; it needs no payment lookup or fulfillment. The signed lifecycle events `refund.updated.v1`, `withdrawal.updated.v1` and `checkout.cancellation.updated.v1` carry `data.resource` with `source: payment`. Their typed resources never convert into payment transactions. Poll request IDs if delivery is delayed.

## Errors and response metadata

API errors retain `httpStatus`, `errorCode`, `providerCode`, `requestId`, `retryAfter`, `outcome` and nested API `details`. Request IDs prefer the response body over `X-Request-ID`; `Retry-After` remains its original string. Non-JSON HTTP failures retain status/headers and an uncertain outcome. Only an explicit `not_applied` outcome confirms no mutation. Managers unwrap response `data`. Read metadata with `getLastMeta()` on the manager or `$tinker->getLastAuthMeta()` for authentication.

```php
try {
    $tinker->transactions()->query(new QueryPaymentRequest('TXN-abc123xyz', Gateway::MPESA));
} catch (\Tinker\Exception\ApiException $error) {
    error_log($error->errorCode . ': ' . $error->getMessage());
}
```

## Development

```sh
composer install
composer lint
composer test
```

MIT License.
