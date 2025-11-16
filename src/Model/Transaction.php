<?php

declare(strict_types=1);

namespace Tinker\Model;

class Transaction
{
    public readonly string $id;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string $status;
    public readonly array $metadata;
    public readonly string $createdAt;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function isSuccessful(): bool
    {
        return 'successful' === $this->status;
    }

    public function isPending(): bool
    {
        return 'pending' === $this->status;
    }
}
