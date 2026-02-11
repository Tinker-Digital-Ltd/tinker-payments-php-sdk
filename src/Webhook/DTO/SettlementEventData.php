<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class SettlementEventData
{
    public readonly string $id;
    public readonly string $status;
    public readonly float $amount;
    public readonly float|null $netAmount;
    public readonly string $currency;
    public readonly string $settlementDate;
    public readonly string $createdAt;
    public readonly string|null $processedAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = (string) ($data['settlement_id'] ?? $data['id'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->amount = (float) ($data['amount'] ?? 0);
        $this->netAmount = isset($data['net_amount']) ? (float) $data['net_amount'] : null;
        $this->currency = (string) ($data['currency'] ?? '');
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
            'settlement_id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'net_amount' => $this->netAmount,
            'currency' => $this->currency,
            'settlement_date' => $this->settlementDate,
            'created_at' => $this->createdAt,
            'processed_at' => $this->processedAt,
        ];
    }
}
