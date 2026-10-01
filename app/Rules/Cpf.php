<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        // 1. rejeita se não tiver exatamente 11 dígitos
        if (strlen($digits) !== 11) {
            $fail('Invalid CPF');

            return;
        }

        // 2. rejeita sequências repetidas como 11111111111
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            $fail('Invalid CPF');

            return;
        }

        // 3. confere os dois dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += $digits[$i] * ($t + 1 - $i);
            }

            $check = ($sum * 10) % 11 % 10;

            if ((int) $digits[$t] !== $check) {
                $fail('Invalid CPF');

                return;
            }
        }
    }
}
