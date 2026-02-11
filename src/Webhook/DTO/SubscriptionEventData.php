<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class SubscriptionEventData
{
    public readonly string $id;
    public readonly string $status;
    public readonly string $planId;
    public readonly string $accountId;
    public readonly string|null $currentPeriodStart;
    public readonly string|null $currentPeriodEnd;
    public readonly string $createdAt;
    public readonly string|null $cancelledAt;
    public readonly string|null $pausedAt;
    public readonly string|null $reactivatedAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = (string) ($data['subscription_id'] ?? $data['id'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->planId = $data['plan_id'] ?? '';
        $this->accountId = $data['account_id'] ?? $data['customer_id'] ?? '';
        $this->currentPeriodStart = $data['current_period_start'] ?? null;
        $this->currentPeriodEnd = $data['current_period_end'] ?? null;
        $this->createdAt = $data['created_at'] ?? $this->currentPeriodStart ?? '';
        $this->cancelledAt = $data['cancelled_at'] ?? null;
        $this->pausedAt = $data['paused_at'] ?? null;
        $this->reactivatedAt = $data['reactivated_at'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->id,
            'status' => $this->status,
            'plan_id' => $this->planId,
            'account_id' => $this->accountId,
            'current_period_start' => $this->currentPeriodStart,
            'current_period_end' => $this->currentPeriodEnd,
            'created_at' => $this->createdAt,
            'cancelled_at' => $this->cancelledAt,
            'paused_at' => $this->pausedAt,
            'reactivated_at' => $this->reactivatedAt,
        ];
    }
}
