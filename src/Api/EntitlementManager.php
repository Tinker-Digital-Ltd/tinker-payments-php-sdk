<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class EntitlementManager extends BaseManager
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
        $result = $this->request('GET', Endpoints::ENTITLEMENTS_PATH);

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
        return $this->request('POST', Endpoints::ENTITLEMENTS_PATH, $request);
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
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function update(string $id, string $displayName): array
    {
        return $this->request('PATCH', $this->path($id), ['display_name' => $displayName]);
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

    /**
     * @param list<string> $productIds
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function attachProducts(string $id, array $productIds): array
    {
        return $this->request('POST', $this->path($id).'/attach_products', ['product_ids' => $productIds]);
    }

    /**
     * @param list<string> $productIds
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function detachProducts(string $id, array $productIds): array
    {
        return $this->request('POST', $this->path($id).'/detach_products', ['product_ids' => $productIds]);
    }

    private function path(string $id): string
    {
        return Endpoints::ENTITLEMENTS_PATH.'/'.rawurlencode($id);
    }
}
