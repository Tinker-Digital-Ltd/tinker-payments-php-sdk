<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class CancellationRequest
{
    public function __construct(
        public readonly string $reason,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['reason' => $this->reason];
    }
}
