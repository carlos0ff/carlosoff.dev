<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\AuditAction;
use App\Enums\PaymentSource;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Services\Billing\ChargeService;
use App\Services\Billing\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Confirmação de pagamento por evento confiável do provedor (nunca por retorno de página de sucesso).
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentGatewayManager $gateways, ChargeService $charges): JsonResponse
    {
        abort_unless($gateways->has($provider) && $provider !== 'manual', 404);

        $gateway = $gateways->driver($provider);

        if (! $gateway->verifyWebhook($request)) {
            AuditLog::record(AuditAction::Webhook, 'Pagamento', null, $provider, 'Webhook rejeitado: autenticidade inválida', ['actor_type' => 'webhook', 'actor_name' => $provider]);

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        foreach ($gateway->parseWebhook($request) as $event) {
            $charge = Charge::where('gateway', $provider)->where('gateway_charge_id', $event['gateway_charge_id'])->first();
            if (! $charge) {
                continue;
            }

            if ($event['refund']) {
                $charges->refund($charge, $event['amount_cents'], 'Estorno informado pelo provedor', gatewayPaymentId: $event['gateway_payment_id']);
            } else {
                $charges->recordPayment($charge, $event['amount_cents'], Carbon::parse($event['paid_at']), PaymentSource::Webhook, $event['method'], $event['gateway_payment_id']);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
