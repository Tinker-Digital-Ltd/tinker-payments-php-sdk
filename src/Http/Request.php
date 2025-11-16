<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    use MessageTrait;
    private string|null $requestTarget = null;

    public function __construct(private string $method, private UriInterface $uri, StreamFactory $streamFactory)
    {
        $this->streamFactory = $streamFactory;
        $this->protocol = '1.1';
        $this->headers = [];
        $this->headerNames = [];
        $this->body = $streamFactory->createStream();
    }

    public function getRequestTarget(): string
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ('' === $target) {
            $target = '/';
        }

        $query = $this->uri->getQuery();
        if ('' !== $query) {
            $target .= '?'.$query;
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost && '' !== $uri->getHost()) {
            return $new->withHeader('Host', $uri->getHost());
        }

        return $new;
    }
}
