<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SolanaAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // RN-08: base58 (sem 0, O, I, l), entre 32 e 44 caracteres.
        if (! is_string($value) || ! preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $value)) {
            $fail('Invalid Solana address');
        }
    }
}
