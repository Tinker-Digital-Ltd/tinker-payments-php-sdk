<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class IntegrationManager extends BaseManager
{
    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function list(): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->request('GET', Endpoints::INTEGRATIONS_PATH);

        return $result;
    }

    /**
     * @param array<string, mixed> $request `secret`, `event_types`, `enabled`
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function put(string $kind, array $request): array
    {
        return $this->request('PUT', $this->path($kind), $request);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function delete(string $kind): array
    {
        return $this->request('DELETE', $this->path($kind));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function test(string $kind): array
    {
        return $this->request('POST', $this->path($kind).'/test');
    }

    private function path(string $kind): string
    {
        return Endpoints::INTEGRATIONS_PATH.'/'.rawurlencode($kind);
    }
}
