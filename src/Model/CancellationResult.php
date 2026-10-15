<?php

declare(strict_types=1);

namespace Tinker\Model;

final class CancellationResult
{
    public readonly CheckoutCancellation $cancellation;
    public readonly bool $replayed;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->cancellation = new CheckoutCancellation(is_array($data['cancellation'] ?? null) ? $data['cancellation'] : []);
        $this->replayed = true === ($data['replayed'] ?? false);
    }
}
