<?php

declare(strict_types=1);

namespace Tinker\Model\DTO;

final class RefundRequest
{
    public function __construct(
        public readonly string $reason,
        public readonly float|null $amount = null,
        public readonly string|null $note = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter(['reason' => $this->reason, 'amount' => $this->amount, 'note' => $this->note], static fn (mixed $value): bool => null !== $value);
    }
}
