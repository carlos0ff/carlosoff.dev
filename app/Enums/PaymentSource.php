<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Origem da confirmação de um pagamento.
 */
enum PaymentSource: string
{
    use HasOptions;

    case Manual = 'manual';
    case Webhook = 'webhook';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Baixa manual com evidência',
            self::Webhook => 'Webhook do provedor',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Manual => 'warning',
            self::Webhook => 'success',
        };
    }
}
