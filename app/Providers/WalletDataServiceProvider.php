<?php

namespace App\Providers;

use App\WalletData\FakeWalletDataProvider;
use App\WalletData\SolscanMapper;
use App\WalletData\WalletDataProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class WalletDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // bind (not singleton) so tests can change the config between resolutions.
        $this->app->bind(WalletDataProvider::class, function () {
            return match (config('wallet_data.driver')) {
                'fake' => new FakeWalletDataProvider(new SolscanMapper, config('wallet_data.fixtures_path')),
                default => throw new InvalidArgumentException('Unknown SOLSCAN_DRIVER value.'),
            };
        });
    }
}
