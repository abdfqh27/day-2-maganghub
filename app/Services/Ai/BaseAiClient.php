<?php

namespace App\Services\Ai;

use GuzzleHttp\Client as GuzzleClient;
use OpenAI;
use OpenAI\Client;

class BaseAiClient
{
    protected ?Client $client = null;
    protected string $model;
    protected string $baseUri;
    protected string $apiKey;
    protected int $timeout;

    public function __construct(
        ?string $baseUri = null,
        ?string $apiKey = null,
        ?string $model = null,
        int $timeout = 10
    ) {
        $this->baseUri = $baseUri ?? config('services.swift_ai.base_uri', 'https://ukisai.com/api/swift/v1');
        $this->apiKey = $apiKey ?? config('services.swift_ai.api_key', 'none');
        $this->model = $model ?? config('services.swift_ai.model', 'swift');
        $this->timeout = $timeout;
    }

    public function getClient(): Client
    {
        if ($this->client === null) {
            $httpClient = new GuzzleClient([
                'timeout' => $this->timeout,
                'connect_timeout' => 5,
            ]);

            $this->client = OpenAI::factory()
                ->withBaseUri($this->baseUri)
                ->withApiKey($this->apiKey)
                ->withHttpClient($httpClient)
                ->make();
        }

        return $this->client;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
