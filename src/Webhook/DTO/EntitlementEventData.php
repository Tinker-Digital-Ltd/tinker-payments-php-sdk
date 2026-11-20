<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class EntitlementEventData
{
    public readonly string $id;
    public readonly string $transactionId;
    public readonly string $productId;
    public readonly string $externalCustomerId;
    /** @var list<string> */
    public readonly array $entitlementIds;
    public readonly string $store;
    public readonly string $periodType;
    public readonly float|null $amount;
    public readonly string $currency;
    public readonly string|null $purchasedAt;
    public readonly string|null $expirationAt;
    public readonly string|null $presentedOfferingId;
    public readonly string|null $revokedAt;
    public readonly string|null $environment;
    public readonly string|null $originalTransactionId;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = (string) ($data['id'] ?? '');
        $this->transactionId = (string) ($data['transaction_id'] ?? '');
        $this->productId = (string) ($data['product_id'] ?? '');
        $this->externalCustomerId = (string) ($data['external_customer_id'] ?? '');
        $this->entitlementIds = array_values(array_map(strval(...), (array) ($data['entitlement_ids'] ?? [])));
        $this->store = (string) ($data['store'] ?? '');
        $this->periodType = (string) ($data['period_type'] ?? '');
        $this->amount = isset($data['amount']) ? (float) $data['amount'] : null;
        $this->currency = (string) ($data['currency'] ?? '');
        $this->purchasedAt = $data['purchased_at'] ?? null;
        $this->expirationAt = $data['expiration_at'] ?? null;
        $this->presentedOfferingId = $data['presented_offering_id'] ?? null;
        $this->revokedAt = $data['revoked_at'] ?? null;
        $this->environment = $data['environment'] ?? null;
        $this->originalTransactionId = $data['original_transaction_id'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transactionId,
            'product_id' => $this->productId,
            'external_customer_id' => $this->externalCustomerId,
            'entitlement_ids' => $this->entitlementIds,
            'store' => $this->store,
            'period_type' => $this->periodType,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'purchased_at' => $this->purchasedAt,
            'expiration_at' => $this->expirationAt,
            'presented_offering_id' => $this->presentedOfferingId,
            'revoked_at' => $this->revokedAt,
            'environment' => $this->environment,
            'original_transaction_id' => $this->originalTransactionId,
        ];
    }
}
