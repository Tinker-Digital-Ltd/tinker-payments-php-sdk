<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Webhook\WebhookHandler;

final class OnboardingWebhookTest extends TestCase
{
    public function testGoSignedSetupTestIsRecognizedAndVerified(): void
    {
        $handler = new WebhookHandler();
        $payload = $this->payload();
        $event = $handler->handle($payload);
        self::assertTrue($event->isOnboardingEvent());
        self::assertTrue($event->isSetupTest());
        self::assertNotNull($event->getOnboardingData());
        self::assertStringContainsString('webhook verification', $event->getOnboardingData()->message);
        self::assertNull($event->getPaymentData());
        self::assertNull($handler->handleAsTransaction($payload));
        self::assertTrue($handler->verifySignature($event, 'webhook-secret'));
        self::assertTrue($handler->verifySignature($payload, 'webhook-secret'));
        self::assertFalse($handler->verifySignature($payload, 'wrong-secret'));
        self::assertFalse($handler->verifySignature($payload, ''));
        self::assertFalse($handler->verifySignature(str_replace('verification', 'forged', $payload), 'webhook-secret'));
        self::assertFalse($handler->verifySignature(str_replace('HMAC-SHA256', 'none', $payload), 'webhook-secret'));
        self::assertFalse($handler->verifySignature(str_replace('sha256=', 'invalid=', $payload), 'webhook-secret'));
        self::assertFalse($handler->handle(str_replace('setup.webhook_test', 'onboarding.future', $payload))->isSetupTest());
    }

    public function testUnknownSourceStillFails(): void
    {
        $this->expectException(InvalidPayloadException::class);
        (new WebhookHandler())->handle(str_replace('"source":"onboarding"', '"source":"unknown"', $this->payload()));
    }

    private function payload(): string
    {
        $payload = file_get_contents(\dirname(__DIR__, 2).'/Fixtures/setup-webhook.json');
        self::assertIsString($payload);

        return $payload;
    }
}
