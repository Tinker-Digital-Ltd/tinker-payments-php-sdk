<?php

declare(strict_types=1);

namespace Tinker\Webhook;

use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Webhook\DTO\InvoiceEventData;
use Tinker\Webhook\DTO\PaymentEventData;
use Tinker\Webhook\DTO\SettlementEventData;
use Tinker\Webhook\DTO\SubscriptionEventData;

final class WebhookEvent
{
    public readonly string $id;
    public readonly string $type;
    public readonly string $source;
    public readonly string $timestamp;
    public readonly PaymentEventData|SubscriptionEventData|InvoiceEventData|SettlementEventData $data;
    public readonly WebhookMeta $meta;
    public readonly WebhookSecurity $security;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(array $payload)
    {
        $this->id = $payload['id'];
        $this->type = $payload['type'];
        $this->source = $payload['source'];
        $this->timestamp = $payload['timestamp'];
        $this->data = $this->createEventData($payload['data'], $this->source);
        $this->meta = new WebhookMeta($payload['meta'] ?? []);
        $this->security = new WebhookSecurity($payload['security'] ?? []);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createEventData(array $data, string $source): PaymentEventData|SubscriptionEventData|InvoiceEventData|SettlementEventData
    {
        return match ($source) {
            'payment' => new PaymentEventData($data),
            'subscription' => new SubscriptionEventData($data),
            'invoice' => new InvoiceEventData($data),
            'settlement' => new SettlementEventData($data),
            default => throw new InvalidPayloadException("Unknown webhook source: {$source}", ExceptionCode::INVALID_PAYLOAD),
        };
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
