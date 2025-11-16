<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

final class RequestFactory implements RequestFactoryInterface
{
    private readonly StreamFactory $streamFactory;

    public function __construct()
    {
        $this->streamFactory = new StreamFactory();
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        return new Request($method, $uri, $this->streamFactory);
    }
}
