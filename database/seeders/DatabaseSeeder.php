<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'password', // the User model hashes it
        ]);

        $pf = $user->accounts()->create([
            'type' => AccountType::Individual,
            'name' => 'Demo User',
            'document' => '52998224725',
        ]);

        $companyA = $user->accounts()->create([
            'type' => AccountType::Company,
            'name' => 'Demo Company A',
            'document' => '11222333000181',
        ]);

        $companyB = $user->accounts()->create([
            'type' => AccountType::Company,
            'name' => 'Demo Company B',
            'document' => '11444777000161',
        ]);

        $shared = Wallet::create(['address' => '2YcwVbKx9L25Jpaj2vfWSXD5UKugZumWjzEe6suBUJi2']);
        $ownA = Wallet::create(['address' => 'ob2htHLoCu2P6tX7RrNVtiG1mYTas8NGJEVLaFEUngk']);
        $ownB = Wallet::create(['address' => 'GThUX1Atko4tqhN2NaiTazWSeFWMuiUvfFnyJyUghFMJ']);
        $ownPf = Wallet::create(['address' => '9LbdhSersRjbkcWWdd4Xpgr8ADi16dwzMU1tM1Ddsq79']);

        // One wallet shared by the PF and Company A, with a different label in each link.
        $pf->wallets()->attach($shared->id, ['label' => 'Personal savings']);
        $companyA->wallets()->attach($shared->id, ['label' => 'Company treasury']);

        // At least one wallet per account.
        $pf->wallets()->attach($ownPf->id, ['label' => 'Personal spending']);
        $companyA->wallets()->attach($ownA->id, ['label' => 'Company A operations']);
        $companyB->wallets()->attach($ownB->id, ['label' => 'Company B operations']);
    }
}
