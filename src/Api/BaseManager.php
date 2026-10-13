<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Exception\NetworkException;
use Tinker\Model\ApiMeta;

abstract class BaseManager
{
    protected ApiMeta|null $lastMeta = null;

    public function __construct(
        protected readonly Configuration $config,
        protected readonly ClientInterface $httpClient,
        protected readonly RequestFactoryInterface $requestFactory,
        protected readonly AuthenticationManager $authManager,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException|ClientExceptionInterface
     */
    protected function request(
        string $method,
        string $endpoint,
        array $data = [],
    ): array {
        $baseUrl = rtrim($this->config->getBaseUrl(), '/');
        $endpoint = ltrim($endpoint, '/');
        $url = $baseUrl.'/'.$endpoint;
        $token = $this->authManager->getToken();
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');

        if (!empty($data)) {
            $json = json_encode($data);
            if (false === $json) {
                throw new InvalidPayloadException('Failed to encode request data: '.json_last_error_msg(), ExceptionCode::INVALID_PAYLOAD);
            }
            $request->getBody()->write($json);
            $request->getBody()->rewind();
        }

        try {
            $response = $this->httpClient->sendRequest($request);
            $result = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 400) {
                throw $this->apiError($response, $result);
            }

            if (is_array($result) && array_key_exists('success', $result)) {
                $this->lastMeta = new ApiMeta(is_array($result['meta'] ?? null) ? $result['meta'] : []);

                if (false === $result['success']) {
                    throw $this->apiError($response, $result);
                }

                $data = $result['data'] ?? [];

                return is_array($data) ? $data : ['value' => $data];
            }

            return is_array($result) ? $result : [];
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new NetworkException('Failed to communicate with Tinker API: '.$e->getMessage(), ExceptionCode::NETWORK_ERROR, $e);
        }
    }

    private function apiError(ResponseInterface $response, mixed $result): ApiException
    {
        $error = is_array($result) && is_array($result['error'] ?? null) ? $result['error'] : [];

        return new ApiException(
            $this->extractErrorMessage($result),
            httpStatus: $response->getStatusCode(),
            errorCode: is_string($error['code'] ?? null) ? $error['code'] : null,
            providerCode: is_string($error['provider_code'] ?? null) ? $error['provider_code'] : null,
            requestId: (is_string($error['request_id'] ?? null) ? $error['request_id'] : null) ?: ($response->getHeaderLine('X-Request-ID') ?: null),
            retryAfter: $response->getHeaderLine('Retry-After') ?: null,
            outcome: is_string($error['outcome'] ?? null) ? $error['outcome'] : null,
        );
    }

    private function extractErrorMessage(mixed $result): string
    {
        if (is_array($result)) {
            if (isset($result['error']) && is_array($result['error'])) {
                return (string) ($result['error']['message'] ?? $result['error']['code'] ?? 'Unknown error');
            }

            if (isset($result['message'])) {
                return (string) $result['message'];
            }
        }

        return 'Unknown error';
    }

    public function getLastMeta(): ApiMeta|null
    {
        return $this->lastMeta;
    }
}
