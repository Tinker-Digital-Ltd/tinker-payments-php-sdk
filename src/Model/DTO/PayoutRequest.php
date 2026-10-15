<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class PayoutRequest
{
    public function __construct(
        public readonly string $payoutMethodId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly float $expectedFeeAmount,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['payout_method_id' => $this->payoutMethodId, 'amount' => $this->amount, 'currency' => $this->currency, 'expected_fee_amount' => $this->expectedFeeAmount];
    }
}
