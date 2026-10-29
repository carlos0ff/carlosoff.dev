<?php

/*
|--------------------------------------------------------------------------
| Gestão de clientes, projetos, contratos e cobranças
|--------------------------------------------------------------------------
|
| Prazos de cobrança/suspensão NÃO ficam aqui: são definidos por contrato
| (tabela `billing_policies`). Os valores abaixo são apenas os padrões
| sugeridos ao criar uma política nova e podem ser alterados no painel.
|
*/

return [

    'company' => [
        'name' => env('GESTAO_COMPANY_NAME', 'Carlos Off Desenvolvimento'),
        'document' => env('GESTAO_COMPANY_DOCUMENT'),
        'email' => env('GESTAO_COMPANY_EMAIL', env('MAIL_FROM_ADDRESS')),
        'city' => env('GESTAO_COMPANY_CITY'),
    ],

    // Fuso dos campos de data/hora digitados no painel (o app grava em UTC).
    'timezone' => env('GESTAO_TIMEZONE', 'America/Sao_Paulo'),

    'policy_defaults' => [
        'reminder_days_before' => 3,
        'overdue_notice_interval_days' => 3,
        'grace_days' => 10,
        'pre_suspension_notice_days' => 5,
    ],

    'api' => [
        // TLS obrigatório para a API. Só pode ser desligado em ambiente local/testes.
        'require_https' => env('GESTAO_API_REQUIRE_HTTPS', ! in_array(env('APP_ENV'), ['local', 'testing'], true)),
        'clock_skew_seconds' => 300,
        'nonce_ttl_seconds' => 900,
        'rate_limit_per_minute' => (int) env('GESTAO_API_RATE_LIMIT', 60),
        // Após rotacionar, a credencial antiga continua válida por este período (troca sem downtime).
        'rotation_grace_hours' => 24,
    ],

    'monitoring' => [
        'timeout_seconds' => 10,
        'connect_timeout_seconds' => 5,
        // Falhas seguidas necessárias para abrir um incidente técnico.
        'incident_after_failures' => 3,
        'ssl_warning_days' => 21,
        'domain_warning_days' => 30,
        'rdap_bootstrap_url' => env('GESTAO_RDAP_URL', 'https://rdap.org/domain/'),
    ],

    'signature' => [
        'driver' => env('SIGNATURE_DRIVER', 'clicksign'),

        'clicksign' => [
            'base_url' => env('CLICKSIGN_BASE_URL', 'https://sandbox.clicksign.com/api/v3'),
            'access_token' => env('CLICKSIGN_ACCESS_TOKEN'),
            'webhook_secret' => env('CLICKSIGN_WEBHOOK_SECRET'),
            'timeout_seconds' => 20,
            'deadline_days' => 30,
        ],
    ],

    'payments' => [
        // Só `manual` existe hoje. Um driver real precisa implementar App\Services\Billing\Gateways\PaymentGateway.
        'driver' => env('PAYMENT_DRIVER', 'manual'),
    ],

    'retention' => [
        // Registros fiscais/financeiros precisam ser guardados por prazo legal; confirme com o seu contador.
        'financial_years' => 5,
        'audit_years' => 5,
    ],

];
