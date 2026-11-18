<?php

declare(strict_types=1);

namespace Tinker\Webhook\DTO;

final class CustomerEventData
{
    /** @var list<string> */
    public readonly array $transferredFrom;
    /** @var list<string> */
    public readonly array $transferredTo;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->transferredFrom = array_values(array_map(strval(...), (array) ($data['transferred_from'] ?? [])));
        $this->transferredTo = array_values(array_map(strval(...), (array) ($data['transferred_to'] ?? [])));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['transferred_from' => $this->transferredFrom, 'transferred_to' => $this->transferredTo];
    }
}
