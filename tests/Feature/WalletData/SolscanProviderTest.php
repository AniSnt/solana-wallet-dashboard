<?php

use App\WalletData\Exceptions\InvalidAddress;
use App\WalletData\Exceptions\ProviderMisconfigured;
use App\WalletData\Exceptions\ProviderUnavailable;
use App\WalletData\SolscanMapper;
use App\WalletData\SolscanWalletDataProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const SOLSCAN_TEST_ADDRESS = '2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2';

function solscanFixture(string $name): string
{
    return file_get_contents(base_path('tests/Fixtures/solscan/'.$name));
}

function solscanProvider(?string $key = 'secret'): SolscanWalletDataProvider
{
    return new SolscanWalletDataProvider(
        new SolscanMapper,
        Cache::store('array'),
        'https://pro-api.solscan.io/v2.0',
        $key,
        timeout: 5,
        attempts: 3,
        retrySleepMs: 0,
        cooldown: 15,
    );
}

function solscanQuery(Request $request): array
{
    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

    return $query;
}

beforeEach(fn () => Http::preventStrayRequests());

it('sends the token header and the right parameters to each endpoint', function () {
    Http::fake([
        '*account/detail*' => Http::response(solscanFixture('account-detail.json')),
        '*account/token-accounts*' => Http::response(solscanFixture('token-accounts.json')),
        '*account/transactions*' => Http::response(solscanFixture('transactions.json')),
    ]);

    $provider = solscanProvider();
    $provider->balance(SOLSCAN_TEST_ADDRESS);
    $provider->tokens(SOLSCAN_TEST_ADDRESS);
    $provider->transactions(SOLSCAN_TEST_ADDRESS, before: 'LAST-SIGNATURE', limit: 10);

    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/account/detail')
        && $r->hasHeader('token', 'secret')
        && solscanQuery($r) == ['address' => SOLSCAN_TEST_ADDRESS]);

    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/account/token-accounts')
        && $r->hasHeader('token', 'secret')
        && solscanQuery($r) == ['address' => SOLSCAN_TEST_ADDRESS, 'type' => 'token', 'page' => '1', 'page_size' => '40', 'hide_zero' => 'true']);

    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/account/transactions')
        && $r->hasHeader('token', 'secret')
        && solscanQuery($r) == ['address' => SOLSCAN_TEST_ADDRESS, 'limit' => '10', 'before' => 'LAST-SIGNATURE']);
});

it('serves the second call from cache and bypasses it on refresh', function () {
    Http::fake(['*account/detail*' => Http::response(solscanFixture('account-detail.json'))]);

    $provider = solscanProvider();
    $provider->balance(SOLSCAN_TEST_ADDRESS);
    $provider->balance(SOLSCAN_TEST_ADDRESS);

    Http::assertSentCount(1);

    $provider->balance(SOLSCAN_TEST_ADDRESS, fresh: true);

    Http::assertSentCount(2);
});

it('shares the cache between callers of the same address', function () {
    Http::fake(['*account/detail*' => Http::response(solscanFixture('account-detail.json'))]);

    solscanProvider()->balance(SOLSCAN_TEST_ADDRESS);
    solscanProvider()->balance(SOLSCAN_TEST_ADDRESS);

    Http::assertSentCount(1);
});

it('retries a transient 5xx and then succeeds', function () {
    Http::fake(['*account/detail*' => Http::sequence()
        ->push(solscanFixture('error-500.json'), 500)
        ->push(solscanFixture('account-detail.json'), 200)]);

    $balance = solscanProvider()->balance(SOLSCAN_TEST_ADDRESS);

    expect($balance->sol)->toBe('0.01193428');
    Http::assertSentCount(2);
});

it('gives up after the attempt limit and raises a domain exception', function () {
    Http::fake(['*account/detail*' => Http::response(solscanFixture('error-500.json'), 500)]);

    expect(fn () => solscanProvider()->balance(SOLSCAN_TEST_ADDRESS))->toThrow(ProviderUnavailable::class);

    Http::assertSentCount(3);
});

it('treats 429 as unavailable, does not retry and stops calling the API for a while', function () {
    Http::fake(['*account/detail*' => Http::response(solscanFixture('error-429.json'), 429)]);

    $provider = solscanProvider();

    expect(fn () => $provider->balance(SOLSCAN_TEST_ADDRESS))->toThrow(ProviderUnavailable::class)
        ->and(fn () => $provider->balance(SOLSCAN_TEST_ADDRESS, fresh: true))->toThrow(ProviderUnavailable::class);

    Http::assertSentCount(1);
});

it('maps a 400 to InvalidAddress without retrying', function () {
    Http::fake(['*account/detail*' => Http::response(solscanFixture('error-400.json'), 400)]);

    expect(fn () => solscanProvider()->balance(SOLSCAN_TEST_ADDRESS))->toThrow(InvalidAddress::class);

    Http::assertSentCount(1);
});

it('maps a 401 to a configuration error, logs it and does not retry', function () {
    Log::spy();
    Http::fake(['*account/detail*' => Http::response(solscanFixture('error-401.json'), 401)]);

    expect(fn () => solscanProvider()->balance(SOLSCAN_TEST_ADDRESS))->toThrow(ProviderMisconfigured::class);

    Http::assertSentCount(1);
    Log::shouldHaveReceived('error')->once();
});

it('turns a timeout into a domain exception', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28: Operation timed out');
    });

    expect(fn () => solscanProvider()->balance(SOLSCAN_TEST_ADDRESS))->toThrow(ProviderUnavailable::class);
});

it('does not call the API without a key', function () {
    Http::fake();

    expect(fn () => solscanProvider(key: null)->balance(SOLSCAN_TEST_ADDRESS))->toThrow(ProviderMisconfigured::class);

    Http::assertNothingSent();
});
