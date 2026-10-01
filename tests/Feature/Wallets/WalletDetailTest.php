<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Models\Wallet;
use App\WalletData\Dto\WalletBalance;
use App\WalletData\Exceptions\ProviderUnavailable;
use App\WalletData\FakeWalletDataProvider;
use App\WalletData\SolscanMapper;
use App\WalletData\WalletDataProvider;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

const WALLET_DETAIL_ADDRESS = '2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2';

function detailUser(string $cpf): array
{
    $user = User::factory()->create();
    $account = $user->accounts()->create([
        'type' => AccountType::Individual,
        'name' => $user->name,
        'document' => $cpf,
    ]);

    return [$user, $account];
}

function detailLink(Account $account, string $label): Wallet
{
    $wallet = Wallet::firstOrCreate(['address' => WALLET_DETAIL_ADDRESS]);
    $account->wallets()->attach($wallet->id, ['label' => $label]);

    return $wallet;
}

it('shows balance, tokens and transactions to the owner', function () {
    [$a, $accountA] = detailUser('52998224725');
    $wallet = detailLink($accountA, 'Main');

    $this->actingAs($a)->get(route('accounts.wallet', [$accountA, $wallet]))
        ->assertOk()
        ->assertSee('0.01193428')
        ->assertSee('1200000000')
        ->assertSee('99999999999999.999999')
        ->assertSee('2k5SKZo9tAgK3w24');
});

it('returns 404 for the wallet page of another user account', function () {
    [, $accountA] = detailUser('52998224725');
    [$b] = detailUser('11144477735');
    $wallet = detailLink($accountA, 'A');

    $this->actingAs($b)->get(route('accounts.wallet', [$accountA, $wallet]))->assertNotFound();
});

it('returns 404 for a wallet not linked to the account, even knowing its id', function () {
    [$a, $accountA] = detailUser('52998224725');
    [, $accountB] = detailUser('11144477735');
    $wallet = detailLink($accountB, 'B');

    $this->actingAs($a)->get(route('accounts.wallet', [$accountA, $wallet]))->assertNotFound();
    $this->actingAs($a)->get(route('accounts.wallet', [$accountB, $wallet]))->assertNotFound();
});

it('does not reveal the label of another account that shares the wallet', function () {
    [$a, $accountA] = detailUser('52998224725');
    [, $accountB] = detailUser('11144477735');
    $wallet = detailLink($accountA, 'label-of-A');
    detailLink($accountB, 'label-of-B');

    $this->actingAs($a)->get(route('accounts.wallet', [$accountA, $wallet]))
        ->assertOk()
        ->assertSee('label-of-A')
        ->assertDontSee('label-of-B');
});

it('keeps the other blocks working when one block fails', function () {
    app()->bind(WalletDataProvider::class, fn () => new class(new FakeWalletDataProvider(new SolscanMapper, base_path('tests/Fixtures/solscan'))) implements WalletDataProvider
    {
        public function __construct(private FakeWalletDataProvider $inner) {}

        public function balance(string $address, bool $fresh = false): WalletBalance
        {
            return $this->inner->balance($address, $fresh);
        }

        public function tokens(string $address, bool $fresh = false): array
        {
            throw new ProviderUnavailable('down');
        }

        public function transactions(string $address, ?string $before = null, int $limit = 20, bool $fresh = false): array
        {
            return $this->inner->transactions($address, $before, $limit, $fresh);
        }
    });

    [$a, $accountA] = detailUser('52998224725');
    $wallet = detailLink($accountA, 'Main');

    $this->actingAs($a)->get(route('accounts.wallet', [$accountA, $wallet]))
        ->assertOk()
        ->assertSee('0.01193428')
        ->assertSee('Temporarily unavailable')
        ->assertSee('2k5SKZo9tAgK3w24');
});

it('loads more transactions using the cursor', function () {
    [$a, $accountA] = detailUser('52998224725');
    $wallet = detailLink($accountA, 'Main');

    Volt::actingAs($a)->test('accounts.wallet', ['account' => $accountA->id, 'wallet' => $wallet->id])
        ->assertSee('2k5SKZo9tAgK3w24')
        ->assertDontSee('5VERv8NMvzbJMEkV')
        ->call('loadMore')
        ->assertSee('5VERv8NMvzbJMEkV')
        ->assertSee('2k5SKZo9tAgK3w24')
        ->call('loadMore')
        ->assertDontSee('Load more');
});

it('refresh bypasses the cache', function () {
    config(['wallet_data.driver' => 'solscan', 'wallet_data.solscan.api_key' => 'secret']);

    Http::fake([
        '*account/detail*' => Http::response(file_get_contents(base_path('tests/Fixtures/solscan/account-detail.json'))),
        '*account/token-accounts*' => Http::response(file_get_contents(base_path('tests/Fixtures/solscan/token-accounts.json'))),
        '*account/transactions*' => Http::response(file_get_contents(base_path('tests/Fixtures/solscan/transactions.json'))),
    ]);

    [$a, $accountA] = detailUser('52998224725');
    $wallet = detailLink($accountA, 'Main');

    $component = Volt::actingAs($a)->test('accounts.wallet', ['account' => $accountA->id, 'wallet' => $wallet->id]);
    Http::assertSentCount(3);

    $component->call('refresh');
    Http::assertSentCount(6);
});
