<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\PaymentStatus;

final class InitiationData
{
    public readonly string $payment_reference;
    public readonly PaymentStatus $status;
    public readonly string|null $authorization_url;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->payment_reference = $data['payment_reference'];
        $statusValue = $data['status'] ?? 'pending';
        $this->status = PaymentStatus::from($statusValue);
        $this->authorization_url = $data['authorization_url'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'payment_reference' => $this->payment_reference,
            'status' => $this->status->value,
            'authorization_url' => $this->authorization_url,
        ];
    }
}
