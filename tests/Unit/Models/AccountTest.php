<?php

use App\Models\Account;

it('mascara um CPF', function () {
    $account = new Account(['document' => '52998224725']);

    expect($account->maskedDocument())->toBe('529.***.***-25');
});

it('mascara um CNPJ', function () {
    $account = new Account(['document' => '11222333000181']);

    expect($account->maskedDocument())->toBe('11.222.***/****-81');
});
