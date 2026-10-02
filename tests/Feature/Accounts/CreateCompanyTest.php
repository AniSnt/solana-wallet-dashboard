<?php

use App\Enums\AccountType;
use App\Models\User;
use Livewire\Volt\Volt;

function companyTestUser(string $cpf): User
{
    $user = User::factory()->create();
    $user->accounts()->create([
        'type' => AccountType::Individual,
        'name' => $user->name,
        'document' => $cpf,
    ]);

    return $user;
}

it('cria uma PJ ligada ao usuário logado com CNPJ normalizado', function () {
    $user = companyTestUser('52998224725');

    Volt::actingAs($user)->test('accounts.index')
        ->set('name', 'Company A')
        ->set('cnpj', '11.222.333/0001-81')
        ->call('createCompany')
        ->assertHasNoErrors();

    $company = $user->accounts()->where('type', AccountType::Company)->sole();

    expect($company->document)->toBe('11222333000181');
});

it('rejeita um CNPJ inválido', function () {
    $user = companyTestUser('52998224725');

    Volt::actingAs($user)->test('accounts.index')
        ->set('name', 'Company A')
        ->set('cnpj', '11.222.333/0001-82')
        ->call('createCompany')
        ->assertHasErrors(['cnpj']);

    expect($user->accounts()->count())->toBe(1);
});

it('rejeita um CNPJ já usado por outro usuário', function () {
    $owner = companyTestUser('11144477735');
    $owner->accounts()->create([
        'type' => AccountType::Company,
        'name' => 'Owner company',
        'document' => '11222333000181',
    ]);

    $user = companyTestUser('52998224725');

    Volt::actingAs($user)->test('accounts.index')
        ->set('name', 'Company A')
        ->set('cnpj', '11.222.333/0001-81')
        ->call('createCompany')
        ->assertHasErrors(['cnpj']);

    expect($user->accounts()->count())->toBe(1);
});

it('permite várias PJs por usuário', function () {
    $user = companyTestUser('52998224725');

    $component = Volt::actingAs($user)->test('accounts.index');

    $component->set('name', 'Company A')->set('cnpj', '11.222.333/0001-81')->call('createCompany')->assertHasNoErrors();
    $component->set('name', 'Company B')->set('cnpj', '11.444.777/0001-61')->call('createCompany')->assertHasNoErrors();

    expect($user->accounts()->count())->toBe(3);
});
