<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class OfferingManager extends BaseManager
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
        $result = $this->request('GET', Endpoints::OFFERINGS_PATH);

        return $result;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function create(array $request): array
    {
        return $this->request('POST', Endpoints::OFFERINGS_PATH, $request);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function get(string $id): array
    {
        return $this->request('GET', $this->path($id));
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function update(string $id, array $request): array
    {
        return $this->request('PATCH', $this->path($id), $request);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function delete(string $id): array
    {
        return $this->request('DELETE', $this->path($id));
    }

    private function path(string $id): string
    {
        return Endpoints::OFFERINGS_PATH.'/'.rawurlencode($id);
    }
}
