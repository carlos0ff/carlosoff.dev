<?php

namespace App\Http\Middleware;

use App\Models\ApiCredential;
use App\Services\Api\RequestSigner;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticação entre sistemas: HMAC por credencial + janela de tempo + nonce (anti-replay) + TLS.
 *
 * Headers: X-Api-Key, X-Timestamp (unix), X-Nonce, X-Signature (hex).
 */
class AuthenticateApiSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('gestao.api.require_https') && ! $request->secure()) {
            return $this->deny('tls_required', 'HTTPS é obrigatório.', 403);
        }

        $keyId = (string) $request->header('X-Api-Key');
        $timestamp = (string) $request->header('X-Timestamp');
        $nonce = (string) $request->header('X-Nonce');
        $signature = (string) $request->header('X-Signature');

        if ($keyId === '' || $timestamp === '' || $nonce === '' || $signature === '' || ! ctype_digit($timestamp) || ! preg_match('/^[A-Za-z0-9_-]{16,64}$/', $nonce)) {
            return $this->deny('unauthenticated', 'Cabeçalhos de autenticação ausentes ou inválidos.');
        }

        if (abs(time() - (int) $timestamp) > (int) config('gestao.api.clock_skew_seconds')) {
            return $this->deny('stale_request', 'Timestamp fora da janela permitida.');
        }

        $credential = ApiCredential::with(['project', 'user'])->where('key_id', $keyId)->first();

        // Mesma resposta para credencial inexistente, revogada ou assinatura errada (não revela qual).
        if (! $credential || ! $credential->isUsable()) {
            return $this->deny('unauthenticated', 'Credencial inválida.');
        }

        $expected = RequestSigner::sign($credential->secret, $request->method(), $request->getRequestUri(), $timestamp, $nonce, $request->getContent());

        if (! hash_equals($expected, strtolower($signature))) {
            return $this->deny('unauthenticated', 'Credencial inválida.');
        }

        if (! Cache::add("gestao-api-nonce:{$keyId}:{$nonce}", 1, (int) config('gestao.api.nonce_ttl_seconds'))) {
            return $this->deny('replayed_request', 'Requisição repetida (nonce já utilizado).', 409);
        }

        $credential->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        $request->attributes->set('api_credential', $credential);

        return $next($request);
    }

    protected function deny(string $code, string $message, int $status = 401): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
