<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\Gateway;

final class InitiatePaymentRequest
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly Gateway|string $gateway,
        public readonly string $merchantReference,
        public readonly string $returnUrl,
        public readonly string|null $customerPhone = null,
        public readonly string|null $customerEmail = null,
        public readonly string|null $transactionDesc = null,
        public readonly array|null $metadata = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $gatewayValue = $this->gateway instanceof Gateway ? $this->gateway->value : $this->gateway;

        $payload = [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway' => $gatewayValue,
            'merchantReference' => $this->merchantReference,
            'returnUrl' => $this->returnUrl,
        ];

        if (null !== $this->customerPhone) {
            $payload['customerPhone'] = $this->customerPhone;
        }

        if (null !== $this->customerEmail) {
            $payload['customerEmail'] = $this->customerEmail;
        }

        if (null !== $this->transactionDesc) {
            $payload['transactionDesc'] = $this->transactionDesc;
        }

        if (null !== $this->metadata) {
            $payload['metadata'] = $this->metadata;
        }

        return $payload;
    }
}
