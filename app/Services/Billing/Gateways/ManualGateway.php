<?php

namespace App\Services\Billing\Gateways;

use App\Models\Charge;
use Illuminate\Http\Request;

/**
 * Sem provedor integrado: a fonte financeira são as baixas manuais registradas (com responsável e evidência).
 */
class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function createCharge(Charge $charge): array
    {
        return ['id' => null, 'payment_url' => null];
    }

    public function fetchStatus(Charge $charge): array
    {
        $paid = (int) $charge->payments()->sum('amount_cents');

        return ['paid_cents' => $paid, 'settled' => $paid >= $charge->amount_cents];
    }

    public function cancelCharge(Charge $charge): void {}

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): array
    {
        return [];
    }
}
