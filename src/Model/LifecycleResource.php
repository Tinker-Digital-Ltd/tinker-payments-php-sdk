<?php

declare(strict_types=1);

namespace Tinker\Model;

/** A persisted operation; its status is distinct from request acceptance. */
abstract class LifecycleResource
{
    /** @param array<string, mixed> $data */
    public function __construct(protected readonly array $data)
    {
    }

    public function getId(): string
    {
        return (string) ($this->data['id'] ?? '');
    }

    public function getStatus(): string
    {
        return (string) ($this->data['status'] ?? '');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
