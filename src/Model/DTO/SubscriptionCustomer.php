<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class SubscriptionCustomer
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly string $externalCustomerId,
        public readonly string $name,
        public readonly string|null $email = null,
        public readonly string|null $phone = null,
        public readonly array|null $metadata = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'external_customer_id' => $this->externalCustomerId,
            'name' => $this->name,
        ];

        if (null !== $this->email) {
            $data['email'] = $this->email;
        }

        if (null !== $this->phone) {
            $data['phone'] = $this->phone;
        }

        if (null !== $this->metadata) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
