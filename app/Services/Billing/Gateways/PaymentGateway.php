<?php

namespace App\Services\Billing\Gateways;

use App\Models\Charge;
use Illuminate\Http\Request;

/**
 * Camada adaptável a provedores de pagamento. Um driver real (Asaas, Mercado Pago, Efí...) implementa esta interface.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Registra a cobrança no provedor. Retorna o id externo e o link de pagamento, quando houver.
     *
     * @return array{id: string|null, payment_url: string|null}
     */
    public function createCharge(Charge $charge): array;

    /**
     * Consulta a situação NA FONTE financeira (usada antes de qualquer suspensão).
     *
     * @return array{paid_cents: int, settled: bool}
     *
     * @throws GatewayUnavailableException quando o provedor não responde ou responde algo inesperado
     */
    public function fetchStatus(Charge $charge): array;

    public function cancelCharge(Charge $charge): void;

    /**
     * Webhook autêntico? (assinatura/token do provedor). Nunca confiar em retorno de página de sucesso.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Eventos de pagamento confirmados contidos no webhook.
     *
     * @return list<array{gateway_charge_id: string, gateway_payment_id: string, amount_cents: int, paid_at: string, method: string|null, refund: bool}>
     */
    public function parseWebhook(Request $request): array;
}
