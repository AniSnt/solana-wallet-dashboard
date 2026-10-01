<?php

use App\WalletData\Exceptions\ProviderUnavailable;
use App\WalletData\SolscanMapper;
use App\WalletData\Units;

it('converts lamports to SOL', function () {
    expect(Units::sol('11934280'))->toBe('0.01193428')
        ->and(Units::sol('1000000000'))->toBe('1')
        ->and(Units::sol('0'))->toBe('0');
});

it('keeps precision above PHP_INT_MAX', function () {
    // PHP_INT_MAX + 1
    expect(Units::sol('9223372036854775808'))->toBe('9223372036.854775808');
});

it('converts token amounts with different decimals', function () {
    expect(Units::toDecimal('12000000000000', 4))->toBe('1200000000')
        ->and(Units::toDecimal('123456789', 6))->toBe('123.456789')
        ->and(Units::toDecimal('42', 0))->toBe('42');
});

it('maps a balance with a lamports number larger than PHP_INT_MAX', function () {
    $json = '{"success":true,"data":{"account":"x","lamports":9223372036854775808}}';

    $balance = (new SolscanMapper)->balance($json);

    expect($balance->lamports)->toBe('9223372036854775808')
        ->and($balance->sol)->toBe('9223372036.854775808');
});

it('maps tokens using amount_str', function () {
    $json = '{"success":true,"data":[{"token_account":"ta","token_address":"tk","amount":12000000000000,"amount_str":"12000000000000","token_decimals":4,"owner":"o"}]}';

    $tokens = (new SolscanMapper)->tokens($json);

    expect($tokens)->toHaveCount(1)
        ->and($tokens[0]->amount)->toBe('1200000000')
        ->and($tokens[0]->rawAmount)->toBe('12000000000000');
});

it('rejects an error envelope', function () {
    (new SolscanMapper)->balance('{"success":false,"errors":{"code":1100,"message":"x"}}');
})->throws(ProviderUnavailable::class);
