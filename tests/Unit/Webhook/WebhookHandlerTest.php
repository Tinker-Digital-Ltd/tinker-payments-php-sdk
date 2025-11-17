<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;
use Tinker\Enum\PaymentStatus;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Model\Transaction;
use Tinker\Webhook\WebhookHandler;

final class WebhookHandlerTest extends TestCase
{
    private WebhookHandler $webhookHandler;

    protected function setUp(): void
    {
        $this->webhookHandler = new WebhookHandler();
    }

    public function testHandleWithArrayPayload(): void
    {
        $payload = [
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ];

        $transaction = $this->webhookHandler->handle($payload);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $callbackData = $transaction->getCallbackData();
        $this->assertNotNull($callbackData);
        $this->assertSame('pay_abc123', $callbackData->id);
        $this->assertSame('TXN-abc123xyz', $callbackData->reference);
        $this->assertSame(100.00, $callbackData->amount);
        $this->assertSame('KES', $callbackData->currency);
        $this->assertSame(PaymentStatus::SUCCESS, $callbackData->status);
        $this->assertSame('mpesa', $callbackData->channel);
        $this->assertSame('2024-01-15T10:30:00Z', $callbackData->paidAt);
        $this->assertSame('2024-01-15T10:25:00Z', $callbackData->createdAt);
        $this->assertSame(PaymentStatus::SUCCESS, $transaction->status);
    }

    public function testHandleWithJsonStringPayload(): void
    {
        $payload = json_encode([
            'id' => 'pay_abc123',
            'status' => 'pending',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => null,
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ]);

        $transaction = $this->webhookHandler->handle($payload);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $callbackData = $transaction->getCallbackData();
        $this->assertNotNull($callbackData);
        $this->assertSame('pay_abc123', $callbackData->id);
        $this->assertSame(PaymentStatus::PENDING, $callbackData->status);
        $this->assertSame(PaymentStatus::PENDING, $transaction->status);
    }

    public function testHandleThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('Invalid JSON payload');
        $this->expectExceptionCode(ExceptionCode::INVALID_PAYLOAD);

        $this->webhookHandler->handle('invalid json');
    }
}
