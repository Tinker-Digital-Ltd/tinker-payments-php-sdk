<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

use Tinker\Enum\PaymentStatus;

final class InitiationData
{
    public readonly string $paymentReference;
    public readonly PaymentStatus $status;
    public readonly string|null $authorizationUrl;
    public readonly string|null $clientSecret;
    public readonly string|null $publishableKey;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->paymentReference = (string) ($data['paymentReference'] ?? $data['payment_reference'] ?? '');
        $statusValue = $data['status'] ?? 'pending';
        $this->status = PaymentStatus::from($statusValue);
        $this->authorizationUrl = $data['authorizationUrl'] ?? $data['authorization_url'] ?? null;
        $this->clientSecret = $data['clientSecret'] ?? $data['client_secret'] ?? null;
        $this->publishableKey = $data['publishableKey'] ?? $data['publishable_key'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paymentReference' => $this->paymentReference,
            'status' => $this->status->value,
            'authorizationUrl' => $this->authorizationUrl,
            'clientSecret' => $this->clientSecret,
            'publishableKey' => $this->publishableKey,
        ];
    }
}
