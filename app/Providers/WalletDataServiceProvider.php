<?php

namespace App\Providers;

use App\WalletData\FakeWalletDataProvider;
use App\WalletData\SolscanMapper;
use App\WalletData\SolscanWalletDataProvider;
use App\WalletData\WalletDataProvider;
use Illuminate\Support\Facades\Cache;
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
                'solscan' => new SolscanWalletDataProvider(
                    new SolscanMapper,
                    Cache::store(),
                    config('wallet_data.solscan.base_url'),
                    config('wallet_data.solscan.api_key'),
                    config('wallet_data.solscan.timeout'),
                    config('wallet_data.solscan.attempts'),
                    config('wallet_data.solscan.retry_sleep_ms'),
                    config('wallet_data.solscan.cooldown'),
                ),
                default => throw new InvalidArgumentException('Unknown SOLSCAN_DRIVER value.'),
            };
        });
    }
}
