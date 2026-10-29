<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Valores monetários são sempre inteiros em centavos; aqui só formatação e conversão de entrada.
 */
class Money
{
    public static function format(?int $cents): string
    {
        return 'R$ '.number_format(($cents ?? 0) / 100, 2, ',', '.');
    }

    /**
     * Aceita "1.234,56", "1234,56", "1234.56" ou "1234".
     */
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return $value * 100;
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/[^\d,.\-]/', '', $normalized) ?? '';

        if (str_contains($normalized, ',')) {
            $normalized = str_replace(['.', ','], ['', '.'], $normalized);
        }

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("Valor monetário inválido: {$value}");
        }

        return (int) round(((float) $normalized) * 100);
    }

    public static function toInput(?int $cents): string
    {
        return $cents === null ? '' : number_format($cents / 100, 2, ',', '.');
    }
}
