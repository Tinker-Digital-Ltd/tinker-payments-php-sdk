<?php

declare(strict_types=1);

namespace Tinker\Auth;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tinker\Config\Configuration;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\NetworkException;
use Tinker\Http\StreamFactory;

class AuthenticationManager
{
    private string|null $token = null;
    private int|null $expiresAt = null;
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

        $url = Endpoints::AUTH_TOKEN_URL;
        $request = $this->requestFactory->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json');

        $body = $this->streamFactory->createStream('credentials='.urlencode($credentials));
        $request = $request->withBody($body);

        try {
            $response = $this->httpClient->sendRequest($request);
            $result = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 400) {
                throw new ApiException($result['message'] ?? 'Authentication failed', ExceptionCode::AUTHENTICATION_ERROR);
            }

            if (!isset($result['token'])) {
                throw new NetworkException('Invalid authentication response: token missing', ExceptionCode::AUTHENTICATION_ERROR);
            }

            $this->token = $result['token'];
            $expiresIn = $result['expires_in'] ?? 3600;
            $this->expiresAt = time() + $expiresIn;

            return $this->token;
        } catch (ApiException|NetworkException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new NetworkException('Failed to authenticate: '.$e->getMessage(), ExceptionCode::AUTHENTICATION_ERROR, $e);
        }
    }
}
