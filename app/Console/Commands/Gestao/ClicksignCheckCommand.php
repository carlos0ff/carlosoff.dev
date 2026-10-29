<?php

namespace App\Console\Commands\Gestao;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

#[Signature('gestao:clicksign-check')]
#[Description('Verifica se o token da Clicksign (sandbox ou produção) é aceito pela API v3')]
class ClicksignCheckCommand extends Command
{
    public function handle(): int
    {
        $token = config('gestao.signature.clicksign.access_token');
        $base = rtrim((string) config('gestao.signature.clicksign.base_url'), '/');

        if (blank($token)) {
            $this->components->error('CLICKSIGN_ACCESS_TOKEN não definido no .env.');

            return self::FAILURE;
        }

        try {
            $response = Http::timeout(15)->withHeaders(['Authorization' => $token, 'Accept' => 'application/vnd.api+json'])->get("{$base}/envelopes", ['page[size]' => 1]);
        } catch (Throwable $exception) {
            $this->components->error("Falha de conexão: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->components->error("A API respondeu HTTP {$response->status()}: ".mb_substr($response->body(), 0, 300));

            return self::FAILURE;
        }

        $this->components->info("Token aceito por {$base}.");
        $this->line(filled(config('gestao.signature.clicksign.webhook_secret')) ? 'Segredo do webhook: configurado.' : 'ATENÇÃO: CLICKSIGN_WEBHOOK_SECRET não definido — webhooks serão rejeitados.');
        $this->line('URL do webhook para cadastrar na Clicksign: '.route('webhooks.signature', 'clicksign'));

        return self::SUCCESS;
    }
}
