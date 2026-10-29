<?php

namespace App\Services\Api;

/**
 * Assinatura HMAC-SHA256 das requisições entre o painel central e os agentes.
 *
 * canonical = MÉTODO \n CAMINHO?QUERY \n TIMESTAMP \n NONCE \n SHA256(corpo)
 * O mesmo algoritmo assina as respostas de comandos (o agente valida antes de executar).
 */
class RequestSigner
{
    public static function canonical(string $method, string $pathWithQuery, string $timestamp, string $nonce, string $body): string
    {
        return implode("\n", [strtoupper($method), $pathWithQuery, $timestamp, $nonce, hash('sha256', $body)]);
    }

    public static function sign(string $secret, string $method, string $pathWithQuery, string $timestamp, string $nonce, string $body): string
    {
        return hash_hmac('sha256', self::canonical($method, $pathWithQuery, $timestamp, $nonce, $body), $secret);
    }

    public static function signResponse(string $secret, string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp."\n".hash('sha256', $body), $secret);
    }
}
