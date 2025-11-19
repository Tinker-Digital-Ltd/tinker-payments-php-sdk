<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class SettlementEventData
{
    public readonly string $id;
    public readonly string $status;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string $settlementDate;
    public readonly string $createdAt;
    public readonly string|null $processedAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->status = $data['status'];
        $this->amount = (float) $data['amount'];
        $this->currency = $data['currency'];
        $this->settlementDate = $data['settlement_date'] ?? '';
        $this->createdAt = $data['created_at'] ?? '';
        $this->processedAt = $data['processed_at'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'settlement_date' => $this->settlementDate,
            'created_at' => $this->createdAt,
            'processed_at' => $this->processedAt,
        ];
    }
}
