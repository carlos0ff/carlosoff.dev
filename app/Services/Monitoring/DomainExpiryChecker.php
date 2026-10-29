<?php

namespace App\Services\Monitoring;

use App\Models\Domain;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Consulta a data de expiração via RDAP (protocolo público de registro). Se falhar, mantém a data informada manualmente.
 */
class DomainExpiryChecker
{
    public function check(Domain $domain): ?Carbon
    {
        try {
            $response = Http::timeout(15)->acceptJson()->get(config('gestao.monitoring.rdap_bootstrap_url').rawurlencode($domain->name));
            if (! $response->successful()) {
                return null;
            }

            $expiration = collect($response->json('events', []))->firstWhere('eventAction', 'expiration');
            if (! $expiration || empty($expiration['eventDate'])) {
                return null;
            }

            $expiresAt = Carbon::parse($expiration['eventDate']);
            $domain->forceFill(['expires_at' => $expiresAt->toDateString(), 'expires_checked_at' => now()])->save();

            return $expiresAt;
        } catch (Throwable) {
            return null;
        }
    }
}
