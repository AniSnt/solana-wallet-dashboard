<?php

use App\Rules\SolanaAddress;

function addressIsValid(string $value): bool
{
    $failed = false;

    (new SolanaAddress)->validate('address', $value, function () use (&$failed) {
        $failed = true;
    });

    return ! $failed;
}

it('aceita um endereço válido', function () {
    expect(addressIsValid('2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2'))->toBeTrue();
});

it('rejeita um endereço curto', function () {
    expect(addressIsValid('abc'))->toBeFalse();
});

it('rejeita caracteres fora do base58', function () {
    // o 0 (zero) não existe em base58
    expect(addressIsValid('0YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2'))->toBeFalse();
});
