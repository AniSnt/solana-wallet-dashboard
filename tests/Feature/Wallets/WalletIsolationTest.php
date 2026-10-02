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

it('permite ao dono abrir as carteiras da própria conta', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');

    $this->actingAs($a)->get(route('accounts.wallets', $accountA))->assertOk();
});

it('retorna 404 quando o usuário B abre as carteiras da conta do usuário A', function () {
    [, $accountA] = makeUserWithPf('52998224725');
    [$b] = makeUserWithPf('11144477735');

    $this->actingAs($b)->get(route('accounts.wallets', $accountA))->assertNotFound();
});

it('não deixa o usuário B executar ações Livewire na conta do usuário A', function () {
    [, $accountA] = makeUserWithPf('52998224725');
    [$b] = makeUserWithPf('11144477735');

    expect(fn () => Volt::actingAs($b)->test('accounts.wallets', ['account' => $accountA->id]))
        ->toThrow(ModelNotFoundException::class);
});

it('reaproveita a mesma carteira e mantém os labels privados entre contas', function () {
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

it('não mostra carteira que não está vinculada à conta do usuário', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');
    Wallet::create(['address' => SHARED_ADDRESS]);

    $this->actingAs($a)->get(route('accounts.wallets', $accountA))
        ->assertOk()
        ->assertDontSee(SHARED_ADDRESS);
});

it('rejeita vincular a mesma carteira duas vezes à mesma conta', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');

    linkWallet($a, $accountA, SHARED_ADDRESS, '')->assertHasNoErrors();
    linkWallet($a, $accountA, SHARED_ADDRESS, '')->assertHasErrors(['address']);

    expect($accountA->wallets()->count())->toBe(1);
});

it('rejeita um endereço Solana inválido', function () {
    [$a, $accountA] = makeUserWithPf('52998224725');

    linkWallet($a, $accountA, 'abc', '')->assertHasErrors(['address']);

    expect(Wallet::count())->toBe(0);
});

it('desvincular de uma conta não afeta a outra conta', function () {
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
