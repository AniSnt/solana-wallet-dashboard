<?php

use App\Rules\Cnpj;

function cnpjIsValid(string $value): bool
{
    $failed = false;

    (new Cnpj)->validate('cnpj', $value, function () use (&$failed) {
        $failed = true;
    });

    return ! $failed;
}

it('aceita um CNPJ válido sem máscara', function () {
    expect(cnpjIsValid('11222333000181'))->toBeTrue();
});

it('aceita um CNPJ válido com máscara', function () {
    expect(cnpjIsValid('11.222.333/0001-81'))->toBeTrue();
});

it('rejeita sequência repetida', function () {
    expect(cnpjIsValid('11.111.111/1111-11'))->toBeFalse();
});

it('rejeita dígito verificador errado', function () {
    expect(cnpjIsValid('11222333000182'))->toBeFalse();
});

it('rejeita tamanho errado', function () {
    expect(cnpjIsValid('1234567'))->toBeFalse();
});
