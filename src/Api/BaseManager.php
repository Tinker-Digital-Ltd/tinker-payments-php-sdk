<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Exception\NetworkException;

abstract class BaseManager
{
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
                $message = $result['message'] ?? $result['error'] ?? 'Unknown error';
                throw new ApiException($message, ExceptionCode::API_ERROR);
            }

            return $result ?? [];
        } catch (ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new NetworkException('Failed to communicate with Tinker API: '.$e->getMessage(), ExceptionCode::NETWORK_ERROR, $e);
        }
    }
}
