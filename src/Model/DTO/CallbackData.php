<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\PaymentStatus;

final class CallbackData
{
    public readonly string $id;
    public readonly PaymentStatus $status;
    public readonly string $reference;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string|null $paid_at;
    public readonly string $created_at;
    public readonly string $channel;

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
        $this->paid_at = $data['paid_at'] ?? null;
        $this->created_at = $data['created_at'];
        $this->channel = $data['channel'];
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
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'channel' => $this->channel,
        ];
    }
}
