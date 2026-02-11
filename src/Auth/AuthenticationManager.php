<?php

declare(strict_types=1);

namespace Tinker\Auth;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\NetworkException;
use Tinker\Http\StreamFactory;
use Tinker\Model\ApiMeta;

class AuthenticationManager
{
    private string|null $token = null;
    private int|null $expiresAt = null;
    private ApiMeta|null $lastMeta = null;
    private readonly StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly Configuration $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        StreamFactoryInterface|null $streamFactory = null,
    ) {
        $this->streamFactory = $streamFactory ?? new StreamFactory();
    }

    public function getToken(): string
    {
        if ($this->isTokenValid()) {
            return $this->token;
        }

        return $this->fetchToken();
    }

    private function isTokenValid(): bool
    {
        if (null === $this->token || null === $this->expiresAt) {
            return false;
        }

        return time() < ($this->expiresAt - 60);
    }

    private function fetchToken(): string
    {
        $credentials = base64_encode(
            $this->config->getApiPublicKey().':'.$this->config->getApiSecretKey(),
        );

        $url = $this->config->getAuthUrl();
        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json');

        $body = $this->streamFactory->createStream('credentials='.urlencode($credentials));
        $request = $request->withBody($body);

        try {
            $response = $this->httpClient->sendRequest($request);
            $result = json_decode((string) $response->getBody(), true);
            $authData = $this->extractAuthData($result);

            if ($response->getStatusCode() >= 400) {
                throw new ApiException($this->extractErrorMessage($result), ExceptionCode::AUTHENTICATION_ERROR);
            }

            if (!isset($authData['token'])) {
                throw new NetworkException('Invalid authentication response: token missing', ExceptionCode::AUTHENTICATION_ERROR);
            }

            $this->token = $authData['token'];
            $expiresIn = (int) ($authData['expires_in'] ?? 3600);
            $this->expiresAt = time() + $expiresIn;

            return $this->token;
        } catch (ApiException|NetworkException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new NetworkException('Failed to authenticate: '.$e->getMessage(), ExceptionCode::AUTHENTICATION_ERROR, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAuthData(mixed $result): array
    {
        if (is_array($result) && array_key_exists('success', $result)) {
            $this->lastMeta = new ApiMeta(is_array($result['meta'] ?? null) ? $result['meta'] : []);

            if (false === $result['success']) {
                throw new ApiException($this->extractErrorMessage($result), ExceptionCode::AUTHENTICATION_ERROR);
            }

            $data = $result['data'] ?? [];

            return is_array($data) ? $data : [];
        }

        return is_array($result) ? $result : [];
    }

    private function extractErrorMessage(mixed $result): string
    {
        if (is_array($result)) {
            if (isset($result['error']) && is_array($result['error'])) {
                return (string) ($result['error']['message'] ?? $result['error']['code'] ?? 'Authentication failed');
            }

            if (isset($result['message'])) {
                return (string) $result['message'];
            }
        }

        return 'Authentication failed';
    }

    public function getLastMeta(): ApiMeta|null
    {
        return $this->lastMeta;
    }
}
