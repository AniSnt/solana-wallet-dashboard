<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

const SHARED_ADDRESS = '2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2';

function makeUserWithPf(string $cpf): array
{
    $user = User::factory()->create();
    $account = $user->accounts()->create([
        'type' => AccountType::Individual,
        'name' => $user->name,
        'document' => $cpf,
    ]);

    return [$user, $account];
}

function linkWallet(User $user, Account $account, string $address, string $label)
{
    return Volt::actingAs($user)
        ->test('accounts.wallets', ['account' => $account->id])
        ->set('address', $address)
        ->set('label', $label)
        ->call('link');
}

it('lets the owner open their own account wallets', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');

    $this->actingAs($a)->get(route('accounts.wallets', $accountA))->assertOk();
});

it('returns 404 when user B opens the wallets page of user A account', function () {
    [, $accountA] = makeUserWithPf('52998224725');
    [$b] = makeUserWithPf('11144477735');

    $this->actingAs($b)->get(route('accounts.wallets', $accountA))->assertNotFound();
});

it('does not let user B run Livewire actions on the account of user A', function () {
    [, $accountA] = makeUserWithPf('52998224725');
    [$b] = makeUserWithPf('11144477735');

    expect(fn () => Volt::actingAs($b)->test('accounts.wallets', ['account' => $accountA->id]))
        ->toThrow(ModelNotFoundException::class);
});

it('reuses the same wallet and keeps labels private between accounts', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');
    [$b, $accountB] = makeUserWithPf('11144477735');

    linkWallet($a, $accountA, SHARED_ADDRESS, 'label-of-A')->assertHasNoErrors();
    linkWallet($b, $accountB, SHARED_ADDRESS, 'label-of-B')->assertHasNoErrors();

    expect(Wallet::count())->toBe(1);

    $this->actingAs($a)->get(route('accounts.wallets', $accountA))
        ->assertSee('label-of-A')
        ->assertDontSee('label-of-B');

    $this->actingAs($b)->get(route('accounts.wallets', $accountB))
        ->assertSee('label-of-B')
        ->assertDontSee('label-of-A');
});

it('does not show a wallet that is not linked to the user account', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');
    Wallet::create(['address' => SHARED_ADDRESS]);

    $this->actingAs($a)->get(route('accounts.wallets', $accountA))
        ->assertOk()
        ->assertDontSee(SHARED_ADDRESS);
});

it('rejects linking the same wallet twice to the same account', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');

    linkWallet($a, $accountA, SHARED_ADDRESS, '')->assertHasNoErrors();
    linkWallet($a, $accountA, SHARED_ADDRESS, '')->assertHasErrors(['address']);

    expect($accountA->wallets()->count())->toBe(1);
});

it('rejects an invalid Solana address', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');

    linkWallet($a, $accountA, 'abc', '')->assertHasErrors(['address']);

    expect(Wallet::count())->toBe(0);
});

it('unlinking from one account does not affect the other account', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');
    [$b, $accountB] = makeUserWithPf('11144477735');

    linkWallet($a, $accountA, SHARED_ADDRESS, 'A');
    linkWallet($b, $accountB, SHARED_ADDRESS, 'B');

    $wallet = Wallet::firstWhere('address', SHARED_ADDRESS);

    Volt::actingAs($a)
        ->test('accounts.wallets', ['account' => $accountA->id])
        ->call('unlink', $wallet->id);

    expect($accountA->wallets()->count())->toBe(0)
        ->and($accountB->wallets()->count())->toBe(1)
        ->and(Wallet::count())->toBe(1);
});
