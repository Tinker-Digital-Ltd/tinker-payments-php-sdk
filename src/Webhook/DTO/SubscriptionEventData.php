<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class SubscriptionEventData
{
    public readonly string $id;
    public readonly string $status;
    public readonly string $planId;
    public readonly string $accountId;
    public readonly string|null $externalCustomerId;
    public readonly string|null $currentPeriodStart;
    public readonly string|null $currentPeriodEnd;
    public readonly string $createdAt;
    public readonly string|null $cancelledAt;
    public readonly string|null $pausedAt;
    public readonly string|null $reactivatedAt;
    public readonly string $productId;
    /** @var list<string> */
    public readonly array $entitlementIds;
    public readonly string|null $store;
    public readonly string $periodType;
    public readonly string|null $expirationAt;
    public readonly string|null $gracePeriodExpirationAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = (string) ($data['subscription_id'] ?? $data['id'] ?? '');
        $this->status = (string) ($data['status'] ?? '');
        $this->planId = $data['plan_id'] ?? '';
        $this->accountId = $data['account_id'] ?? $data['customer_id'] ?? '';
        $this->externalCustomerId = $data['external_customer_id'] ?? null;
        $this->currentPeriodStart = $data['current_period_start'] ?? null;
        $this->currentPeriodEnd = $data['current_period_end'] ?? null;
        $this->createdAt = $data['created_at'] ?? $this->currentPeriodStart ?? '';
        $this->cancelledAt = $data['cancelled_at'] ?? null;
        $this->pausedAt = $data['paused_at'] ?? null;
        $this->reactivatedAt = $data['reactivated_at'] ?? null;
        $this->productId = (string) ($data['product_id'] ?? '');
        $this->entitlementIds = array_values(array_map(strval(...), (array) ($data['entitlement_ids'] ?? [])));
        $this->store = $data['store'] ?? null;
        $this->periodType = (string) ($data['period_type'] ?? '');
        $this->expirationAt = $data['expiration_at'] ?? null;
        $this->gracePeriodExpirationAt = $data['grace_period_expiration_at'] ?? null;
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
            'external_customer_id' => $this->externalCustomerId,
            'current_period_start' => $this->currentPeriodStart,
            'current_period_end' => $this->currentPeriodEnd,
            'created_at' => $this->createdAt,
            'cancelled_at' => $this->cancelledAt,
            'paused_at' => $this->pausedAt,
            'reactivated_at' => $this->reactivatedAt,
            'product_id' => $this->productId,
            'entitlement_ids' => $this->entitlementIds,
            'store' => $this->store,
            'period_type' => $this->periodType,
            'expiration_at' => $this->expirationAt,
            'grace_period_expiration_at' => $this->gracePeriodExpirationAt,
        ];
    }
}
