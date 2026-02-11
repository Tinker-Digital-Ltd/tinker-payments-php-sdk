<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class InvoiceEventData
{
    public readonly string $id;
    public readonly string $status;
    public readonly string $invoiceNumber;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string $subscriptionId;
    public readonly string $createdAt;
    public readonly string|null $paidAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = (string) ($data['invoice_id'] ?? $data['id'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->invoiceNumber = $data['invoice_number'] ?? '';
        $this->amount = (float) ($data['amount'] ?? 0);
        $this->currency = (string) ($data['currency'] ?? '');
        $this->subscriptionId = $data['subscription_id'] ?? '';
        $this->createdAt = $data['created_at'] ?? '';
        $this->paidAt = $data['paid_at'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->id,
            'status' => $this->status,
            'invoice_number' => $this->invoiceNumber,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'subscription_id' => $this->subscriptionId,
            'created_at' => $this->createdAt,
            'paid_at' => $this->paidAt,
        ];
    }
}
