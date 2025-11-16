<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

abstract class BaseManager
{
    public function __construct(
        protected readonly Configuration $config,
        protected readonly ClientInterface $httpClient,
        protected readonly RequestFactoryInterface $requestFactory,
    ) {
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $baseUrl = rtrim($this->config->getBaseUrl(), '/');
        $endpoint = ltrim($endpoint, '/');
        $url = $baseUrl.'/'.$endpoint;
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer '.$this->config->getApiKey())
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');

        if (!empty($data)) {
            $request->getBody()->write(json_encode($data));
            $request->getBody()->rewind();
        }

        try {
            $response = $this->httpClient->sendRequest($request);
            $result = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 400) {
                throw new ApiException($result['message'] ?? 'Unknown error', $response->getStatusCode());
            }

            return $result;
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new NetworkException('Failed to communicate with Tinker API: '.$e->getMessage());
        }
    }
}
