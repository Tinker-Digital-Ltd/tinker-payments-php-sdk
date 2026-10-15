<?php

declare(strict_types=1);

namespace Tinker\Model;

final class RefundResult
{
    public readonly Refund $refund;
    public readonly bool $replayed;

    /** @var array<string, mixed> */
    public readonly array $payment;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->refund = new Refund(is_array($data['refund'] ?? null) ? $data['refund'] : []);
        $this->replayed = true === ($data['replayed'] ?? false);
        $this->payment = is_array($data['payment'] ?? null) ? $data['payment'] : [];
    }
}
