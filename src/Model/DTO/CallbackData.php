<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\PaymentStatus;

final class CallbackData
{
    public readonly string $id;
    public readonly PaymentStatus|string $status;
    public readonly string $reference;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string|null $paidAt;
    public readonly string $createdAt;
    public readonly string $channel;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $statusValue = $data['status'] ?? 'pending';
        $this->status = PaymentStatus::parse((string) $statusValue);
        $this->reference = $data['reference'];
        $this->amount = (float) $data['amount'];
        $this->currency = $data['currency'];
        $this->paidAt = $data['paid_at'] ?? null;
        $this->createdAt = $data['created_at'];
        $this->channel = $data['channel'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => PaymentStatus::text($this->status),
            'reference' => $this->reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'paid_at' => $this->paidAt,
            'created_at' => $this->createdAt,
            'channel' => $this->channel,
        ];
    }
}
