<?php

namespace App\Support;

/**
 * Validação de CPF/CNPJ (dígitos verificadores).
 */
class BrazilianDocument
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValidCpf(string $value): bool
    {
        $cpf = self::digits($value);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $value): bool
    {
        $cnpj = self::digits($value);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];
        foreach ($weights as $index => $weight) {
            $sum = 0;
            foreach ($weight as $position => $factor) {
                $sum += (int) $cnpj[$position] * $factor;
            }
            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;
            if ((int) $cnpj[12 + $index] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function format(?string $value): string
    {
        $digits = self::digits($value);

        return match (strlen($digits)) {
            11 => vsprintf('%s.%s.%s-%s', [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 3), substr($digits, 9, 2)]),
            14 => vsprintf('%s.%s.%s/%s-%s', [substr($digits, 0, 2), substr($digits, 2, 3), substr($digits, 5, 3), substr($digits, 8, 4), substr($digits, 12, 2)]),
            default => (string) $value,
        };
    }
}
