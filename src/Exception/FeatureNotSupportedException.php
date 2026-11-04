<?php

declare(strict_types=1);

namespace Tinker\Exception;

final class FeatureNotSupportedException extends ApiException
{
    public const string CODE = 'FEATURE_NOT_SUPPORTED';

    public function getGateway(): string|null
    {
        return is_string($this->details['gateway'] ?? null) ? $this->details['gateway'] : null;
    }

    public function getFeature(): string|null
    {
        return is_string($this->details['feature'] ?? null) ? $this->details['feature'] : null;
    }

    /** @return list<string> */
    public function getAlternatives(): array
    {
        return is_array($this->details['alternatives'] ?? null) ? array_values($this->details['alternatives']) : [];
    }
}
