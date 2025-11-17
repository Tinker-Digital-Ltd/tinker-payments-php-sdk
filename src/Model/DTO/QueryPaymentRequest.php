<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\Gateway;

final class QueryPaymentRequest
{
    public function __construct(
        public readonly string $paymentReference,
        public readonly Gateway|string $gateway,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $gatewayValue = $this->gateway instanceof Gateway ? $this->gateway->value : $this->gateway;

        return [
            'payment_reference' => $this->paymentReference,
            'gateway' => $gatewayValue,
        ];
    }
}
