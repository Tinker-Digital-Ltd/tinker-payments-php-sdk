<?php

declare(strict_types=1);

namespace Tinker\Webhook;

final class WebhookSecurity
{
    public readonly string $signature;
    public readonly string $algorithm;

    /**
     * @param array<string, mixed> $security
     */
    public function __construct(array $security)
    {
        $this->signature = $security['signature'] ?? '';
        $this->algorithm = $security['algorithm'] ?? 'HMAC-SHA256';
    }
}
