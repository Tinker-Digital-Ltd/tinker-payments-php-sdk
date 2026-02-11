<?php

declare(strict_types=1);

namespace Tinker\Model;

final class ApiMeta
{
    public readonly string|null $requestId;
    public readonly string|null $timestamp;
    public readonly string|null $environment;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(array $meta)
    {
        $this->requestId = isset($meta['request_id']) ? (string) $meta['request_id'] : null;
        $this->timestamp = isset($meta['timestamp']) ? (string) $meta['timestamp'] : null;
        $this->environment = isset($meta['environment']) ? (string) $meta['environment'] : null;
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'timestamp' => $this->timestamp,
            'environment' => $this->environment,
        ];
    }
}
