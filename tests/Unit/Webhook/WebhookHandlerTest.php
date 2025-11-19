<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;
use Tinker\Enum\PaymentStatus;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Model\Transaction;
use Tinker\Webhook\WebhookEvent;
use Tinker\Webhook\WebhookHandler;

final class WebhookHandlerTest extends TestCase
{
    private WebhookHandler $webhookHandler;

    protected function setUp(): void
    {
        $this->webhookHandler = new WebhookHandler();
    }

    public function testHandleWithNewFormatPaymentEvent(): void
    {
        $payload = [
            'id' => 'evt_abc123xyz',
            'type' => 'payment.completed',
            'source' => 'payment',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'id' => 'pay_abc123',
                'status' => 'success',
                'reference' => 'TXN-abc123xyz',
                'amount' => 100.00,
                'currency' => 'KES',
                'paid_at' => '2024-01-15T10:30:00Z',
                'created_at' => '2024-01-15T10:25:00Z',
                'channel' => 'mpesa',
            ],
            'meta' => [
                'version' => '1.0',
                'app_id' => 'app_123',
                'gateway' => 'mpesa',
            ],
            'security' => [
                'signature' => 'sha256=abc123',
                'algorithm' => 'HMAC-SHA256',
            ],
        ];

        $event = $this->webhookHandler->handle($payload);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertSame('evt_abc123xyz', $event->id);
        $this->assertSame('payment.completed', $event->type);
        $this->assertSame('payment', $event->source);
        $this->assertTrue($event->isPaymentEvent());

        $paymentData = $event->getPaymentData();
        $this->assertNotNull($paymentData);
        $this->assertSame('pay_abc123', $paymentData->id);
        $this->assertSame('TXN-abc123xyz', $paymentData->reference);
        $this->assertSame(100.00, $paymentData->amount);
        $this->assertSame('KES', $paymentData->currency);
        $this->assertSame(PaymentStatus::SUCCESS, $paymentData->status);
        $this->assertSame('mpesa', $paymentData->channel);

        $transaction = $event->toTransaction();
        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testHandleWithSubscriptionEvent(): void
    {
        $payload = [
            'id' => 'evt_sub123',
            'type' => 'subscription.created',
            'source' => 'subscription',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'id' => 'sub_abc123',
                'status' => 'active',
                'plan_id' => 'plan_123',
                'customer_id' => 'cust_123',
                'created_at' => '2024-01-15T10:25:00Z',
            ],
            'meta' => [
                'version' => '1.0',
                'app_id' => 'app_123',
            ],
            'security' => [
                'signature' => 'sha256=abc123',
                'algorithm' => 'HMAC-SHA256',
            ],
        ];

        $event = $this->webhookHandler->handle($payload);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertTrue($event->isSubscriptionEvent());
        $this->assertSame('subscription.created', $event->type);

        $subscriptionData = $event->getSubscriptionData();
        $this->assertNotNull($subscriptionData);
        $this->assertSame('sub_abc123', $subscriptionData->id);
        $this->assertSame('active', $subscriptionData->status);
        $this->assertSame('plan_123', $subscriptionData->planId);
    }

    public function testHandleWithInvoiceEvent(): void
    {
        $payload = [
            'id' => 'evt_inv123',
            'type' => 'invoice.paid',
            'source' => 'invoice',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'id' => 'inv_abc123',
                'status' => 'paid',
                'invoice_number' => 'INV-001',
                'amount' => 200.00,
                'currency' => 'KES',
                'subscription_id' => 'sub_123',
                'created_at' => '2024-01-15T10:25:00Z',
                'paid_at' => '2024-01-15T10:30:00Z',
            ],
            'meta' => [
                'version' => '1.0',
                'app_id' => 'app_123',
            ],
            'security' => [
                'signature' => 'sha256=abc123',
                'algorithm' => 'HMAC-SHA256',
            ],
        ];

        $event = $this->webhookHandler->handle($payload);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertTrue($event->isInvoiceEvent());
        $this->assertSame('invoice.paid', $event->type);

        $invoiceData = $event->getInvoiceData();
        $this->assertNotNull($invoiceData);
        $this->assertSame('inv_abc123', $invoiceData->id);
        $this->assertSame('paid', $invoiceData->status);
        $this->assertSame(200.00, $invoiceData->amount);
    }

    public function testHandleWithSettlementEvent(): void
    {
        $payload = [
            'id' => 'evt_sett123',
            'type' => 'settlement.processed',
            'source' => 'settlement',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'id' => 'sett_abc123',
                'status' => 'processed',
                'amount' => 5000.00,
                'currency' => 'KES',
                'settlement_date' => '2024-01-15',
                'created_at' => '2024-01-15T10:25:00Z',
                'processed_at' => '2024-01-15T10:30:00Z',
            ],
            'meta' => [
                'version' => '1.0',
                'app_id' => 'app_123',
            ],
            'security' => [
                'signature' => 'sha256=abc123',
                'algorithm' => 'HMAC-SHA256',
            ],
        ];

        $event = $this->webhookHandler->handle($payload);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertTrue($event->isSettlementEvent());
        $this->assertSame('settlement.processed', $event->type);

        $settlementData = $event->getSettlementData();
        $this->assertNotNull($settlementData);
        $this->assertSame('sett_abc123', $settlementData->id);
        $this->assertSame('processed', $settlementData->status);
        $this->assertSame(5000.00, $settlementData->amount);
    }

    public function testHandleAsTransactionWithPaymentEvent(): void
    {
        $payload = [
            'id' => 'evt_abc123xyz',
            'type' => 'payment.completed',
            'source' => 'payment',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'id' => 'pay_abc123',
                'status' => 'pending',
                'reference' => 'TXN-abc123xyz',
                'amount' => 100.00,
                'currency' => 'KES',
                'paid_at' => null,
                'created_at' => '2024-01-15T10:25:00Z',
                'channel' => 'mpesa',
            ],
            'meta' => [
                'version' => '1.0',
                'app_id' => 'app_123',
                'gateway' => 'mpesa',
            ],
            'security' => [
                'signature' => 'sha256=abc123',
                'algorithm' => 'HMAC-SHA256',
            ],
        ];

        $transaction = $this->webhookHandler->handleAsTransaction($payload);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $callbackData = $transaction->getCallbackData();
        $this->assertNotNull($callbackData);
        $this->assertSame('pay_abc123', $callbackData->id);
        $this->assertSame(PaymentStatus::PENDING, $callbackData->status);
        $this->assertSame(PaymentStatus::PENDING, $transaction->status);
    }

    public function testHandleAsTransactionWithNonPaymentEvent(): void
    {
        $payload = [
            'id' => 'evt_sub123',
            'type' => 'subscription.created',
            'source' => 'subscription',
            'timestamp' => '2024-01-15T10:30:00Z',
            'data' => [
                'id' => 'sub_abc123',
                'status' => 'active',
            ],
            'meta' => [],
            'security' => [],
        ];

        $transaction = $this->webhookHandler->handleAsTransaction($payload);

        $this->assertNull($transaction);
    }

    public function testHandleThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('Invalid JSON payload');
        $this->expectExceptionCode(ExceptionCode::INVALID_PAYLOAD);

        $this->webhookHandler->handle('invalid json');
    }
}
