<?php

use App\Models\Account;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('cria os dados de demonstração exigidos pelo enunciado', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::where('email', 'demo@example.com')->firstOrFail();

    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->accounts()->where('type', 'individual')->count())->toBe(1)
        ->and($user->accounts()->where('type', 'company')->count())->toBeGreaterThanOrEqual(2)
        ->and(Account::doesntHave('wallets')->count())->toBe(0);

    $sharedId = DB::table('account_wallet')
        ->select('wallet_id')
        ->groupBy('wallet_id')
        ->havingRaw('count(*) > 1')
        ->value('wallet_id');

    $labels = DB::table('account_wallet')->where('wallet_id', $sharedId)->pluck('label');

    expect($sharedId)->not->toBeNull()
        ->and($labels)->toHaveCount(2)
        ->and($labels->unique())->toHaveCount(2);
});
