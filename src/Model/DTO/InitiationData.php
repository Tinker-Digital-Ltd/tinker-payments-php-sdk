<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\PaymentStatus;

final class InitiationData
{
    public readonly string $paymentReference;
    public readonly PaymentStatus $status;
    public readonly string|null $authorizationUrl;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->paymentReference = $data['payment_reference'];
        $statusValue = $data['status'] ?? 'pending';
        $this->status = PaymentStatus::from($statusValue);
        $this->authorizationUrl = $data['authorization_url'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'payment_reference' => $this->paymentReference,
            'status' => $this->status->value,
            'authorization_url' => $this->authorizationUrl,
        ];
    }
}
