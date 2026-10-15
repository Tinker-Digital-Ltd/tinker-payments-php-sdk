<?php

declare(strict_types=1);

namespace Tinker\Model;

final class PayoutResult
{
    public readonly Payout $withdrawal;
    public readonly bool $replayed;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->withdrawal = new Payout(is_array($data['withdrawal'] ?? null) ? $data['withdrawal'] : []);
        $this->replayed = true === ($data['replayed'] ?? false);
    }
}
