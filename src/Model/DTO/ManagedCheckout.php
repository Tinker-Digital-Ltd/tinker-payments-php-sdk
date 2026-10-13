<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

/** Exact integer minor-unit prices for managed recurring billing. */
final class ManagedCheckout
{
    public function __construct(
        public readonly string $externalCustomerId,
        public readonly string $email,
        public readonly string $name,
        public readonly string $currency,
        public readonly int $unitAmount,
        public readonly int $quantity,
        public readonly string $interval,
        public readonly string $returnUrl,
        public readonly string $idempotencyKey,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_customer_id' => $this->externalCustomerId,
            'email' => $this->email,
            'name' => $this->name,
            'currency' => $this->currency,
            'unit_amount' => $this->unitAmount,
            'quantity' => $this->quantity,
            'interval' => $this->interval,
            'return_url' => $this->returnUrl,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}
