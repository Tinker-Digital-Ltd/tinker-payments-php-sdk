<?php

declare(strict_types=1);

namespace Tinker\Model;

use Tinker\Enum\PaymentStatus;

class Transaction
{
    public readonly string|null $id;
    public readonly string|null $payment_reference;
    public readonly string|null $reference;
    public readonly float|null $amount;
    public readonly string|null $currency;
    public readonly PaymentStatus $status;
    public readonly string|null $authorization_url;
    public readonly string|null $channel;
    public readonly string|null $paid_at;
    public readonly string|null $created_at;
    public readonly string|null $createdAt;
    /** @var array<string, mixed>|null */
    public readonly array|null $metadata;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->payment_reference = $data['payment_reference'] ?? $data['reference'] ?? null;
        $this->reference = $data['reference'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $statusValue = $data['status'] ?? 'pending';
        $this->status = PaymentStatus::from($statusValue);
        $this->authorization_url = $data['authorization_url'] ?? null;
        $this->channel = $data['channel'] ?? null;
        $this->paid_at = $data['paid_at'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->createdAt = $data['createdAt'] ?? $data['created_at'] ?? null;
        $this->metadata = $data['metadata'] ?? null;
    }

    public function isSuccessful(): bool
    {
        return PaymentStatus::SUCCESS === $this->status;
    }

    public function isPending(): bool
    {
        return PaymentStatus::PENDING === $this->status;
    }

    public function isCancelled(): bool
    {
        return PaymentStatus::CANCELLED === $this->status;
    }

    public function isFailed(): bool
    {
        return PaymentStatus::FAILED === $this->status;
    }
}
