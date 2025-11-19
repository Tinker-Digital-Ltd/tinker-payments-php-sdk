<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

use Tinker\Enum\PaymentStatus;

final class PaymentEventData
{
    public readonly string $id;
    public readonly PaymentStatus $status;
    public readonly string $reference;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string $channel;
    public readonly string $createdAt;
    public readonly string|null $paidAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $statusValue = $data['status'] ?? 'pending';
        $this->status = PaymentStatus::from($statusValue);
        $this->reference = $data['reference'];
        $this->amount = (float) $data['amount'];
        $this->currency = $data['currency'];
        $this->channel = $data['channel'];
        $this->createdAt = $data['created_at'];
        $this->paidAt = $data['paid_at'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'channel' => $this->channel,
            'created_at' => $this->createdAt,
            'paid_at' => $this->paidAt,
        ];
    }
}
