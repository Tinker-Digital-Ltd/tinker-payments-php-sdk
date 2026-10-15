<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;
use Tinker\Webhook\WebhookHandler;

final class CancellationWebhookTest extends TestCase
{
    public function testSignedCancellationDoesNotBecomeAPayment(): void
    {
        $payload = file_get_contents(\dirname(__DIR__, 2).'/Fixtures/checkout-cancellation-webhook.json');
        self::assertIsString($payload);
        $handler = new WebhookHandler();
        $event = $handler->handle($payload);
        self::assertSame('outcome_unknown', $event->getCancellationData()?->getStatus());
        self::assertNull($event->getPaymentData());
        self::assertNull($event->toTransaction());
        self::assertTrue($handler->verifySignature($event, 'webhook-secret'));
        self::assertTrue($handler->verifySignature($payload, 'webhook-secret'));
        self::assertFalse($handler->verifySignature($payload, 'wrong-secret'));
        self::assertFalse($handler->verifySignature(str_replace('outcome_unknown', 'cancelled', $payload), 'webhook-secret'));
    }

    public function testSignedRefundAndWithdrawalDoNotBecomePayments(): void
    {
        foreach (['refund' => 'getRefundData', 'withdrawal' => 'getPayoutData'] as $name => $getter) {
            $payload = file_get_contents(\dirname(__DIR__, 2).'/Fixtures/'.$name.'-webhook.json');
            self::assertIsString($payload);
            $handler = new WebhookHandler();
            $event = $handler->handle($payload);
            self::assertNotNull($event->{$getter}());
            self::assertNull($event->getPaymentData());
            self::assertNull($event->toTransaction());
            self::assertTrue($handler->verifySignature($event, 'webhook-secret'));
            self::assertTrue($handler->verifySignature($payload, 'webhook-secret'));
            self::assertFalse($handler->verifySignature(str_replace('"amount":100', '"amount":999', $payload), 'webhook-secret'));
        }
    }
}
