<?php

namespace App\Services\Signature;

use InvalidArgumentException;

class SignatureManager
{
    /**
     * @var array<string, class-string<SignatureProvider>>
     */
    protected array $drivers = [
        'clicksign' => ClicksignProvider::class,
    ];

    public function driver(?string $name = null): SignatureProvider
    {
        $name ??= config('gestao.signature.driver');

        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Provedor de assinatura não suportado: {$name}");
        }

        return app($this->drivers[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->drivers[$name]);
    }

    /**
     * Usado pelos testes automatizados para registrar um provedor de teste.
     *
     * @param  class-string<SignatureProvider>  $class
     */
    public function extend(string $name, string $class): void
    {
        $this->drivers[$name] = $class;
    }

    public function isConfigured(): bool
    {
        $name = config('gestao.signature.driver');

        return $name !== 'clicksign' || filled(config('gestao.signature.clicksign.access_token'));
    }
}
