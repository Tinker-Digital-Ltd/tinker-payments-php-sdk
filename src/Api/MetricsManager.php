<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class MetricsManager extends BaseManager
{
    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function get(string|null $from = null, string|null $to = null): array
    {
        $query = http_build_query(array_filter(
            ['from' => $from, 'to' => $to],
            static fn (mixed $value): bool => null !== $value && '' !== $value,
        ));

        return $this->request('GET', Endpoints::METRICS_PATH.('' === $query ? '' : '?'.$query));
    }
}
