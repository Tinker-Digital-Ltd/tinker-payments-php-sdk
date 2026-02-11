<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class CreateSubscriptionRequest
{
    public function __construct(
        public readonly string $planId,
        public readonly string $gateway,
        public readonly SubscriptionCustomer $customer,
        public readonly string|null $paymentMethodId = null,
        public readonly string|null $billingPeriod = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'plan_id' => $this->planId,
            'gateway' => $this->gateway,
            'customer' => $this->customer->toArray(),
        ];

        if (null !== $this->paymentMethodId) {
            $data['payment_method_id'] = $this->paymentMethodId;
        }

        if (null !== $this->billingPeriod) {
            $data['billing_period'] = $this->billingPeriod;
        }

        return $data;
    }
}
