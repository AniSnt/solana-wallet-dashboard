<?php

namespace App\WalletData;

use App\WalletData\Dto\WalletBalance;
use App\WalletData\Exceptions\InvalidAddress;
use App\WalletData\Exceptions\ProviderMisconfigured;
use App\WalletData\Exceptions\ProviderUnavailable;
use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SolscanWalletDataProvider implements WalletDataProvider
{
    private const COOLDOWN_KEY = 'solscan:cooldown';

    public function __construct(
        private readonly SolscanMapper $mapper,
        private readonly Cache $cache,
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $timeout = 10,
        private readonly int $attempts = 3,
        private readonly int $retrySleepMs = 200,
        private readonly int $cooldown = 15,
    ) {}

    public function balance(string $address, bool $fresh = false): WalletBalance
    {
        // Cache is per address, not per account: every account that links this wallet shares it.
        return $this->remember("solscan:balance:{$address}", 60, $fresh, fn () => $this->mapper->balance(
            $this->get('account/detail', ['address' => $address]),
        ));
    }

    public function tokens(string $address, bool $fresh = false): array
    {
        return $this->remember("solscan:tokens:{$address}", 60, $fresh, fn () => $this->mapper->tokens(
            $this->get('account/token-accounts', [
                'address' => $address,
                'type' => 'token',
                'page' => 1,
                'page_size' => 40,
                'hide_zero' => 'true',
            ]),
        ));
    }

    public function transactions(string $address, ?string $before = null, int $limit = 20, bool $fresh = false): array
    {
        $limit = in_array($limit, [10, 20, 30, 40], true) ? $limit : 20;
        $key = "solscan:transactions:{$address}:{$limit}:".($before ?? 'first');

        return $this->remember($key, 30, $fresh, function () use ($address, $before, $limit) {
            $query = ['address' => $address, 'limit' => $limit];

            if ($before !== null) {
                $query['before'] = $before;
            }

            return $this->mapper->transactions($this->get('account/transactions', $query));
        });
    }

    /** Failures are never cached: only a successful load reaches put(). */
    private function remember(string $key, int $ttl, bool $fresh, Closure $load): mixed
    {
        if (! $fresh && $this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $value = $load();
        $this->cache->put($key, $value, $ttl);

        return $value;
    }

    private function get(string $path, array $query): string
    {
        if (blank($this->apiKey)) {
            Log::error('SOLSCAN_API_KEY is missing while SOLSCAN_DRIVER=solscan.');

            throw new ProviderMisconfigured('Wallet data provider is not configured.');
        }

        // After a 429 we stop calling the API for a while instead of hammering it.
        if ($this->cache->has(self::COOLDOWN_KEY)) {
            throw new ProviderUnavailable('Provider is rate limiting us, try again shortly.');
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['token' => $this->apiKey])
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry($this->attempts, $this->retrySleepMs, $this->isTransient(...), throw: false)
                ->get($path, $query);
        } catch (ConnectionException) {
            throw new ProviderUnavailable('Wallet data provider is unreachable.');
        }

        return $this->bodyOrFail($response);
    }

    /** Retry only connection errors and 5xx. Never 400, 401 or 429. */
    private function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $exception->response->serverError());
    }

    private function bodyOrFail(Response $response): string
    {
        if ($response->successful()) {
            return $response->body();
        }

        if ($response->status() === 400) {
            throw new InvalidAddress('The provider rejected this address.');
        }

        if ($response->status() === 401) {
            // Configuration problem: log the detail, show the user nothing specific.
            Log::error('Solscan rejected the API key (401). Check SOLSCAN_API_KEY.');

            throw new ProviderMisconfigured('Wallet data provider is not configured.');
        }

        if ($response->status() === 429) {
            $this->cache->put(self::COOLDOWN_KEY, true, $this->cooldown);
        }

        throw new ProviderUnavailable('Wallet data is temporarily unavailable.');
    }
}
