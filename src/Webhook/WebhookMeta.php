<?php

declare(strict_types=1);

namespace Tinker\Webhook;

final class WebhookMeta
{
    public readonly string $version;
    public readonly string $appId;
    public readonly string|null $gateway;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(array $meta)
    {
        $this->version = $meta['version'] ?? '1.0';
        $this->appId = $meta['app_id'] ?? '';
        $this->gateway = $meta['gateway'] ?? null;
    }
}
