<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Volt\Volt;

it('não permite uma segunda conta PF para o mesmo usuário', function () {
    $user = User::factory()->create();
    $user->accounts()->create([
        'type' => AccountType::Individual,
        'name' => 'Primeira',
        'document' => '52998224725',
    ]);

    expect(fn () => $user->accounts()->create([
        'type' => AccountType::Individual,
        'name' => 'Segunda',
        'document' => '11144477735',
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect($user->accounts()->count())->toBe(1);
});

it('desfaz o usuário quando a criação da conta PF falha', function () {
    Account::creating(fn () => throw new RuntimeException('falha forçada'));

    expect(fn () => Volt::test('auth.register')
        ->set('name', 'Usuário Teste')
        ->set('email', 'rollback@example.com')
        ->set('cpf', '529.982.247-25')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register'))->toThrow(RuntimeException::class);

    expect(User::count())->toBe(0)
        ->and(Account::count())->toBe(0);
});
