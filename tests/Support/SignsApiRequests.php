<?php

namespace Tests\Support;

use App\Models\ApiCredential;
use App\Services\Api\RequestSigner;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Faz requisições assinadas (HMAC) contra a API v1, como o agente faria.
 */
trait SignsApiRequests
{
    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    protected function signedJson(string $method, string $uri, ApiCredential $credential, string $secret, array $body = [], array $headers = [], ?int $timestamp = null, ?string $nonce = null): TestResponse
    {
        $content = $body === [] ? '' : json_encode($body);
        $timestamp = (string) ($timestamp ?? time());
        $nonce ??= Str::random(32);

        $server = $this->transformHeadersToServerVars($headers + [
            'X-Api-Key' => $credential->key_id,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => RequestSigner::sign($secret, $method, $uri, $timestamp, $nonce, $content),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        return $this->call($method, $uri, [], [], [], $server, $content);
    }
}
