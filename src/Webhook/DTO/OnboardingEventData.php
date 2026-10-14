<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class OnboardingEventData
{
    public readonly string $message;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->message = (string) ($data['message'] ?? '');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['message' => $this->message];
    }
}
