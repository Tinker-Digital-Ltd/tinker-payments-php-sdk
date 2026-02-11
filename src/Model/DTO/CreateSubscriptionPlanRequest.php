<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class CreateSubscriptionPlanRequest
{
    /**
     * @param array<int, string> $intervals
     */
    public function __construct(
        public readonly string $name,
        public readonly float $amount,
        public readonly string $currency,
        public readonly array $intervals,
        public readonly string $description = '',
        public readonly bool $isActive = true,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'intervals' => $this->intervals,
            'is_active' => $this->isActive,
        ];
    }
}
