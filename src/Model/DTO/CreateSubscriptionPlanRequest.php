<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class CreateSubscriptionPlanRequest
{
    /**
     * @param array<int, string>        $intervals
     * @param array<string, mixed>|null $featuresJson
     */
    public function __construct(
        public readonly string $name,
        public readonly float $amount,
        public readonly string $currency,
        public readonly array $intervals,
        public readonly string $description = '',
        public readonly bool $isActive = true,
        public readonly array|null $featuresJson = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'intervals' => $this->intervals,
            'is_active' => $this->isActive,
        ];

        if (null !== $this->featuresJson) {
            $payload['features_json'] = $this->featuresJson;
        }

        return $payload;
    }
}
