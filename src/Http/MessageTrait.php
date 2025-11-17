<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    protected string $protocol = '1.1';
    /** @var array<string, array<int, string>> */
    protected array $headers = [];
    /** @var array<string, string> */
    protected array $headerNames = [];
    protected StreamInterface $body;
    protected StreamFactory $streamFactory;

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $name = strtolower($name);
        if (!isset($this->headerNames[$name])) {
            return [];
        }

        $headerName = $this->headerNames[$name];

        return $this->headers[$headerName];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        if (isset($new->headerNames[$normalized])) {
            $headerName = $new->headerNames[$normalized];
            $new->headers[$headerName] = array_merge($new->headers[$headerName], is_array($value) ? $value : [$value]);
        } else {
            $new->headerNames[$normalized] = $name;
            $new->headers[$name] = is_array($value) ? $value : [$value];
        }

        return $new;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $new = clone $this;
        $normalized = strtolower($name);

        if (!isset($new->headerNames[$normalized])) {
            return $new;
        }

        $headerName = $new->headerNames[$normalized];
        unset($new->headers[$headerName], $new->headerNames[$normalized]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}
