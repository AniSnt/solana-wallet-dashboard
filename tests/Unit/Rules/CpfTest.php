<?php

use App\Rules\Cpf;

function cpfIsValid(string $value): bool
{
    $failed = false;

    (new Cpf)->validate('cpf', $value, function () use (&$failed) {
        $failed = true;
    });

    return ! $failed;
}

it('aceita um CPF válido sem máscara', function () {
    expect(cpfIsValid('52998224725'))->toBeTrue();
});

it('aceita um CPF válido com máscara', function () {
    expect(cpfIsValid('529.982.247-25'))->toBeTrue();
});

it('rejeita sequência repetida', function () {
    expect(cpfIsValid('111.111.111-11'))->toBeFalse();
});

it('rejeita dígito verificador errado', function () {
    expect(cpfIsValid('52998224726'))->toBeFalse();
});

it('rejeita tamanho errado', function () {
    expect(cpfIsValid('1234567'))->toBeFalse();
});
