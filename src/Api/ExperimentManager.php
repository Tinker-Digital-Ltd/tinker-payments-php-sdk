<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class ExperimentManager extends BaseManager
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
        $result = $this->request('GET', Endpoints::EXPERIMENTS_PATH);

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
        return $this->request('POST', Endpoints::EXPERIMENTS_PATH, $request);
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
    public function stop(string $id): array
    {
        return $this->request('POST', $this->path($id).'/stop');
    }

    private function path(string $id): string
    {
        return Endpoints::EXPERIMENTS_PATH.'/'.rawurlencode($id);
    }
}
