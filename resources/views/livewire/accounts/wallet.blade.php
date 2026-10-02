<?php

use App\Models\Wallet;
use App\WalletData\Exceptions\ProviderUnavailable;
use App\WalletData\Exceptions\WalletDataException;
use App\WalletData\Units;
use App\WalletData\WalletDataProvider;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public int $accountId;

    #[Locked]
    public int $walletId;

    #[Locked]
    public int $pages = 1;

    public function mount(int $account, int $wallet): void
    {
        $this->accountId = $account;
        $this->walletId = $wallet;
        $this->wallet(); // 404 immediately if this account/wallet pair is not the user's
    }

    // RN-09/09a: the wallet is only reachable through an account of the logged user that links it.
    #[Computed]
    public function wallet(): Wallet
    {
        return auth()->user()->accounts()->findOrFail($this->accountId)
            ->wallets()->findOrFail($this->walletId);
    }

    #[Computed]
    public function balance(): array
    {
        return $this->guard(fn () => $this->provider()->balance($this->wallet->address));
    }

    #[Computed]
    public function tokens(): array
    {
        return $this->guard(fn () => $this->provider()->tokens($this->wallet->address));
    }

    #[Computed]
    public function transactions(): array
    {
        return $this->guard(fn () => $this->loadTransactions(false));
    }

    public function loadMore(): void
    {
        if ($this->pages < 10) {
            $this->pages++;
        }

        unset($this->transactions);
    }

    // Ignores the cache for all three blocks. A failure in one does not stop the others.
    public function refresh(): void
    {
        $address = $this->wallet->address;

        $this->guard(fn () => $this->provider()->balance($address, true));
        $this->guard(fn () => $this->provider()->tokens($address, true));
        $this->guard(fn () => $this->loadTransactions(true));

        unset($this->balance, $this->tokens, $this->transactions);
    }

    private function provider(): WalletDataProvider
    {
        return app(WalletDataProvider::class);
    }

    /** Cursor pagination: each page starts after the last signature of the previous one. */
    private function loadTransactions(bool $fresh): array
    {
        $items = [];
        $before = null;
        $last = [];

        for ($i = 0; $i < $this->pages; $i++) {
            $last = $this->provider()->transactions($this->wallet->address, $before, 20, $fresh);

            if ($last === []) {
                break;
            }

            array_push($items, ...$last);
            $before = end($last)->hash;
        }

        return ['items' => $items, 'hasMore' => $last !== []];
    }

    /** Each block fails on its own, with a message that never leaks provider details. */
    private function guard(callable $load): array
    {
        try {
            return ['data' => $load(), 'error' => null];
        } catch (ProviderUnavailable) {
            return ['data' => null, 'error' => 'Temporarily unavailable. Try Refresh in a moment.'];
        } catch (WalletDataException) {
            return ['data' => null, 'error' => 'This information is not available right now.'];
        }
    }
}; ?>

<div class="page">
    <a href="{{ route('accounts.wallets', $accountId) }}" wire:navigate class="text-lg underline" aria-label="Voltar para as carteiras" title="Voltar para as carteiras">&larr;</a>

    <div>
        <h1 class="text-xl font-semibold">{{ $this->wallet->pivot->label ?? 'Wallet' }}</h1>
        <p class="break-all text-sm text-zinc-500">{{ $this->wallet->address }}</p>
    </div>

    <div>
        <flux:button size="sm" wire:click="refresh">Refresh</flux:button>
    </div>

    <section class="flex flex-col gap-2">
        <h2 class="font-semibold">SOL balance</h2>
        @if ($this->balance['error'])
            <p class="text-sm text-red-500">{{ $this->balance['error'] }}</p>
        @else
            <p>{{ $this->balance['data']->sol }} SOL</p>
        @endif
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="font-semibold">SPL tokens</h2>
        @if ($this->tokens['error'])
            <p class="text-sm text-red-500">{{ $this->tokens['error'] }}</p>
        @elseif ($this->tokens['data'] === [])
            <p class="text-sm text-zinc-500">No tokens.</p>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->tokens['data'] as $token)
                    <li class="py-2">
                        <p>{{ $token->amount }}</p>
                        <p class="break-all text-xs text-zinc-500">{{ $token->tokenAddress }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="font-semibold">Recent transactions</h2>
        @if ($this->transactions['error'])
            <p class="text-sm text-red-500">{{ $this->transactions['error'] }}</p>
        @elseif ($this->transactions['data']['items'] === [])
            <p class="text-sm text-zinc-500">No transactions.</p>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->transactions['data']['items'] as $tx)
                    <li class="py-2">
                        <p class="break-all text-sm">{{ $tx->hash }}</p>
                        <p class="text-xs text-zinc-500">
                            {{ $tx->status }} · {{ date('Y-m-d H:i', $tx->blockTime) }} · fee {{ Units::sol($tx->fee) }} SOL
                        </p>
                    </li>
                @endforeach
            </ul>

            @if ($this->transactions['data']['hasMore'])
                <div>
                    <flux:button size="sm" wire:click="loadMore">Load more</flux:button>
                </div>
            @endif
        @endif
    </section>
</div>