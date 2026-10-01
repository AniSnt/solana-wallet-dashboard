<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        // 1. rejeita se não tiver exatamente 14 dígitos
        if (strlen($digits) !== 14) {
            $fail('Invalid CNPJ');

            return;
        }

        // 2. rejeita sequências repetidas (00000000000000, 11111111111111...)
        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            $fail('Invalid CNPJ');

            return;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weights as $index => $w) {
            $sum = 0;

            foreach ($w as $i => $weight) {
                $sum += $digits[$i] * $weight;
            }

            $rest = $sum % 11;
            $expected = $rest < 2 ? 0 : 11 - $rest;

            if ((int) $digits[12 + $index] !== $expected) {
                $fail('Invalid CNPJ');

                return;
            }
        }

    }
}
