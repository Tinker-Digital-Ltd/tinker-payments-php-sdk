<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class SubscriptionEventData
{
    public readonly string $id;
    public readonly string $status;
    public readonly string $planId;
    public readonly string $customerId;
    public readonly string $createdAt;
    public readonly string|null $cancelledAt;
    public readonly string|null $pausedAt;
    public readonly string|null $reactivatedAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->status = $data['status'];
        $this->planId = $data['plan_id'] ?? '';
        $this->customerId = $data['customer_id'] ?? '';
        $this->createdAt = $data['created_at'] ?? '';
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
            'status' => $this->status,
            'plan_id' => $this->planId,
            'customer_id' => $this->customerId,
            'created_at' => $this->createdAt,
            'cancelled_at' => $this->cancelledAt,
            'paused_at' => $this->pausedAt,
            'reactivated_at' => $this->reactivatedAt,
        ];
    }
}
