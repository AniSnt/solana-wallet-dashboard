<?php

use App\WalletData\FakeWalletDataProvider;
use App\WalletData\WalletDataProvider;

const ADDRESS = '2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2';

it('usa o driver fake por padrão', function () {
    expect(app(WalletDataProvider::class))->toBeInstanceOf(FakeWalletDataProvider::class);
});

it('retorna o saldo em SOL a partir da fixture', function () {
    expect(app(WalletDataProvider::class)->balance(ADDRESS)->sol)->toBe('0.01193428');
});

it('retorna tokens, inclusive uma quantidade maior que PHP_INT_MAX', function () {
    $tokens = app(WalletDataProvider::class)->tokens(ADDRESS);

    expect($tokens)->toHaveCount(2)
        ->and($tokens[0]->amount)->toBe('1200000000')
        ->and($tokens[1]->amount)->toBe('99999999999999.999999');
});

it('pagina as transações por cursor', function () {
    $provider = app(WalletDataProvider::class);

    $page1 = $provider->transactions(ADDRESS);
    $page2 = $provider->transactions(ADDRESS, before: $page1[0]->hash);

    expect($page1)->toHaveCount(1)
        ->and($page2)->toHaveCount(1)
        ->and($page2[0]->hash)->not->toBe($page1[0]->hash)
        ->and($provider->transactions(ADDRESS, before: $page2[0]->hash))->toBe([]);
});
