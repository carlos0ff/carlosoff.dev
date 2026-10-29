<?php

namespace App\Services\Billing;

use App\Services\Billing\Gateways\ManualGateway;
use App\Services\Billing\Gateways\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, class-string<PaymentGateway>>
     */
    protected array $drivers = [
        'manual' => ManualGateway::class,
    ];

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= config('gestao.payments.driver', 'manual');

        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Gateway de pagamento não suportado: {$name}");
        }

        return app($this->drivers[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->drivers[$name]);
    }

    /**
     * @param  class-string<PaymentGateway>  $class
     */
    public function extend(string $name, string $class): void
    {
        $this->drivers[$name] = $class;
    }
}
