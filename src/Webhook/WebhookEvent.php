<?php

declare(strict_types=1);

namespace Tinker\Webhook;

use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Webhook\DTO\InvoiceEventData;
use Tinker\Webhook\DTO\OnboardingEventData;
use Tinker\Webhook\DTO\PaymentEventData;
use Tinker\Webhook\DTO\SettlementEventData;
use Tinker\Webhook\DTO\SubscriptionEventData;

final class WebhookEvent
{
    public readonly string $id;
    public readonly string $type;
    public readonly string $source;
    public readonly string $timestamp;
    public readonly PaymentEventData|SubscriptionEventData|InvoiceEventData|SettlementEventData|OnboardingEventData $data;
    public readonly WebhookMeta $meta;
    public readonly WebhookSecurity $security;
    /** @var array<string, mixed> */
    public readonly array $rawData;
    /** @var array<string, mixed> */
    public readonly array $rawMeta;
    public readonly \stdClass $signatureData;
    public readonly \stdClass $signatureMeta;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload, \stdClass|null $original = null)
    {
        $this->id = $payload['id'];
        $this->type = $payload['type'];
        $this->source = $payload['source'];
        $this->timestamp = $payload['timestamp'];
        $this->rawData = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $this->rawMeta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $this->signatureData = ($original->data ?? null) instanceof \stdClass ? $original->data : (object) $this->rawData;
        $this->signatureMeta = ($original->meta ?? null) instanceof \stdClass ? $original->meta : (object) $this->rawMeta;
        $this->data = $this->createEventData($this->rawData, $this->source);
        $this->meta = new WebhookMeta($this->rawMeta);
        $this->security = new WebhookSecurity($payload['security'] ?? []);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createEventData(array $data, string $source): PaymentEventData|SubscriptionEventData|InvoiceEventData|SettlementEventData|OnboardingEventData
    {
        return match ($source) {
            'onboarding' => new OnboardingEventData($data),
            'payment' => new PaymentEventData($data),
            'subscription' => new SubscriptionEventData($data),
            'invoice' => new InvoiceEventData($data),
            'settlement' => new SettlementEventData($data),
            default => throw new InvalidPayloadException("Unknown webhook source: {$source}", ExceptionCode::INVALID_PAYLOAD),
        };
    }

    public function isOnboardingEvent(): bool
    {
        return 'onboarding' === $this->source;
    }

    /** Parsing identifies the test; verify its signature before acknowledging. */
    public function isSetupTest(): bool
    {
        return $this->isOnboardingEvent() && 'setup.webhook_test' === $this->type;
    }

    public function getOnboardingData(): OnboardingEventData|null
    {
        return $this->data instanceof OnboardingEventData ? $this->data : null;
    }

    public function isPaymentEvent(): bool
    {
        return 'payment' === $this->source;
    }

    public function isSubscriptionEvent(): bool
    {
        return 'subscription' === $this->source;
    }

    public function isInvoiceEvent(): bool
    {
        return 'invoice' === $this->source;
    }

    public function isSettlementEvent(): bool
    {
        return 'settlement' === $this->source;
    }

    public function getPaymentData(): PaymentEventData|null
    {
        return $this->data instanceof PaymentEventData ? $this->data : null;
    }

    public function getSubscriptionData(): SubscriptionEventData|null
    {
        return $this->data instanceof SubscriptionEventData ? $this->data : null;
    }

    public function getInvoiceData(): InvoiceEventData|null
    {
        return $this->data instanceof InvoiceEventData ? $this->data : null;
    }

    public function getSettlementData(): SettlementEventData|null
    {
        return $this->data instanceof SettlementEventData ? $this->data : null;
    }

    public function toTransaction(): \Tinker\Model\Transaction|null
    {
        if (!$this->isPaymentEvent() || !($this->data instanceof PaymentEventData)) {
            return null;
        }

        return new \Tinker\Model\Transaction($this->data->toArray());
    }
}
