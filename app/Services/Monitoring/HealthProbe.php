<?php

namespace App\Services\Monitoring;

use App\Models\ClientProject;
use App\Models\Domain;
use App\Models\HealthCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Verificação externa (disponibilidade HTTP, tempo de resposta e validade do certificado SSL).
 */
class HealthProbe
{
    public function __construct(protected IncidentTracker $incidents) {}

    public function probe(ClientProject $project): ?HealthCheck
    {
        if (! $project->production_url) {
            return null;
        }

        $started = microtime(true);
        $status = null;
        $error = null;

        try {
            $response = Http::timeout((int) config('gestao.monitoring.timeout_seconds'))
                ->connectTimeout((int) config('gestao.monitoring.connect_timeout_seconds'))
                ->withUserAgent('GestaoMonitor/1.0')
                ->withoutRedirecting()
                ->get($project->production_url);
            $status = $response->status();
            $up = $status < 500 && $status !== 0;
            if (! $up) {
                $error = "HTTP {$status}";
            }
        } catch (Throwable $exception) {
            $up = false;
            // Nunca grava URL com query string nem corpo de resposta.
            $error = Str::limit(preg_replace('/\?\S*/', '', $exception->getMessage()) ?? 'Falha de conexão', 200);
        }

        $elapsed = (int) round((microtime(true) - $started) * 1000);
        $sslExpiresAt = $this->sslExpiry($project->production_url);

        $check = HealthCheck::create([
            'client_project_id' => $project->id,
            'source' => 'probe',
            'is_up' => $up,
            'http_status' => $status,
            'response_time_ms' => $elapsed,
            'ssl_expires_at' => $sslExpiresAt,
            'error' => $error,
            'checked_at' => now(),
        ]);

        if ($sslExpiresAt && $project->primary_domain) {
            Domain::where('client_project_id', $project->id)->where('name', $project->primary_domain)->update(['ssl_expires_at' => $sslExpiresAt->toDateString()]);
        }

        $this->incidents->evaluate($project, 'probe');

        return $check;
    }

    public function sslExpiry(string $url): ?Carbon
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            return null;
        }

        try {
            $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false, 'SNI_enabled' => true, 'peer_name' => $parts['host']]]);
            $client = @stream_socket_client("ssl://{$parts['host']}:".($parts['port'] ?? 443), $errno, $errstr, (int) config('gestao.monitoring.connect_timeout_seconds'), STREAM_CLIENT_CONNECT, $context);
            if (! $client) {
                return null;
            }
            $certificate = stream_context_get_params($client)['options']['ssl']['peer_certificate'] ?? null;
            fclose($client);
            $info = $certificate ? openssl_x509_parse($certificate) : false;

            return $info ? Carbon::createFromTimestamp($info['validTo_time_t']) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
