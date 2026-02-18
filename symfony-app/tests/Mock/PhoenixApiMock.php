<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class PhoenixApiMock implements HttpClientInterface
{
    private array $responses = [];
    private array $requests = [];

    public function setResponse(string $url, array $responseData, int $statusCode = 200): void
    {
        $this->responses[$url] = [
            'data' => json_encode($responseData),
            'status' => $statusCode,
            'headers' => ['content-type' => ['application/json']]
        ];
    }

    public function getRequests(): array
    {
        return $this->requests;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'options' => $options
        ];

        $responseKey = $url;
        
        if (!isset($this->responses[$responseKey])) {
            throw new \RuntimeException("No mock response configured for URL: {$url}");
        }

        return new MockResponse($this->responses[$responseKey]);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        throw new \RuntimeException('Stream not implemented in mock');
    }

    public function withOptions(array $options): static
    {
        return $this;
    }
}

class MockResponse implements ResponseInterface
{
    private array $responseInfo;

    public function __construct(array $responseInfo)
    {
        $this->responseInfo = $responseInfo;
    }

    public function getStatusCode(): int
    {
        return $this->responseInfo['status'];
    }

    public function getHeaders(bool $throw = true): array
    {
        return $this->responseInfo['headers'];
    }

    public function getContent(bool $throw = true): string
    {
        return $this->responseInfo['data'];
    }

    public function toArray(bool $throw = true): array
    {
        return json_decode($this->responseInfo['data'], true);
    }

    public function cancel(): void
    {
    }

    public function getInfo(string $type = null): mixed
    {
        return null;
    }
}
