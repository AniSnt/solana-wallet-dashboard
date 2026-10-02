<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('cpf', '529.982.247-25')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('o cadastro cria o usuário e a conta PF juntos', function () {
    Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('cpf', '529.982.247-25')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $user = User::where('email', 'test@example.com')->firstOrFail();
    $account = $user->accounts()->sole();

    expect($account->type)->toBe(AccountType::Individual)
        ->and($account->document)->toBe('52998224725');
});

test('o cadastro rejeita CPF inválido e não grava nada', function () {
    Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('cpf', '111.111.111-11')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['cpf']);

    expect(User::count())->toBe(0)
        ->and(Account::count())->toBe(0);
});

test('o cadastro rejeita CPF duplicado', function () {
    $owner = User::factory()->create();
    $owner->accounts()->create([
        'type' => AccountType::Individual,
        'name' => $owner->name,
        'document' => '52998224725',
    ]);

    Volt::test('auth.register')
        ->set('name', 'Other User')
        ->set('email', 'other@example.com')
        ->set('cpf', '529.982.247-25')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['cpf']);

    expect(User::count())->toBe(1);
});
