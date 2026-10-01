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

it('accepts a valid address', function () {
    expect(addressIsValid('2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2'))->toBeTrue();
});

it('rejects a short address', function () {
    expect(addressIsValid('abc'))->toBeFalse();
});

it('rejects characters outside base58', function () {
    // o 0 (zero) não existe em base58
    expect(addressIsValid('0YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2'))->toBeFalse();
});