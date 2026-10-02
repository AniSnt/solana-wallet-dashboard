@php
    $accounts = auth()->user()->accounts()->withCount('wallets')->orderByDesc('type')->orderBy('name')->get();

    $companies = $accounts->filter(fn ($account) => $account->type === \App\Enums\AccountType::Company)->count();

    $linkedWallets = \Illuminate\Support\Facades\DB::table('account_wallet')
        ->whereIn('account_id', $accounts->pluck('id'))
        ->distinct()
        ->count('wallet_id');

    // Últimos vínculos, só das contas do próprio usuário.
    $recent = \Illuminate\Support\Facades\DB::table('account_wallet')
        ->join('wallets', 'wallets.id', '=', 'account_wallet.wallet_id')
        ->join('accounts', 'accounts.id', '=', 'account_wallet.account_id')
        ->whereIn('account_wallet.account_id', $accounts->pluck('id'))
        ->orderByDesc('account_wallet.created_at')
        ->limit(5)
        ->get(['accounts.id as account_id', 'wallets.id as wallet_id', 'wallets.address', 'account_wallet.label', 'accounts.name as account_name']);
@endphp

<x-layouts.app title="Painel">
    <div class="page-wide">
        <div class="rounded-2xl bg-gradient-to-r from-indigo-600/90 to-indigo-500/70 p-6 text-white">
            <h1 class="text-2xl font-semibold">Olá, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-indigo-100">Acompanhe as carteiras Solana de cada uma das suas contas.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <flux:button size="sm" href="{{ route('accounts.index') }}" wire:navigate>Gerenciar contas</flux:button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/60">
                <flux:icon name="building-office" class="size-8 text-indigo-500" />
                <div>
                    <p class="text-sm text-zinc-500">Contas</p>
                    <p class="text-3xl font-semibold">{{ $accounts->count() }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/60">
                <flux:icon name="briefcase" class="size-8 text-violet-500" />
                <div>
                    <p class="text-sm text-zinc-500">Contas PJ</p>
                    <p class="text-3xl font-semibold">{{ $companies }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/60">
                <flux:icon name="banknotes" class="size-8 text-emerald-500" />
                <div>
                    <p class="text-sm text-zinc-500">Wallets vinculadas</p>
                    <p class="text-3xl font-semibold">{{ $linkedWallets }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="flex flex-col gap-3">
                <h2 class="font-semibold">Suas contas</h2>
                @foreach ($accounts as $account)
                    <a href="{{ route('accounts.wallets', $account) }}" wire:navigate
                       class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 transition hover:border-indigo-500 hover:bg-indigo-500/5 dark:border-zinc-700">
                        <div>
                            <p class="font-medium">{{ $account->name }}</p>
                            <p class="text-sm text-zinc-500">
                                {{ $account->type === \App\Enums\AccountType::Individual ? 'PF' : 'PJ' }}
                                · {{ $account->maskedDocument() }}
                            </p>
                        </div>
                        <span class="rounded-full bg-indigo-500/10 px-3 py-1 text-xs text-indigo-400">
                            {{ $account->wallets_count }} {{ $account->wallets_count === 1 ? 'wallet' : 'wallets' }}
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="flex flex-col gap-3">
                <h2 class="font-semibold">Últimas carteiras vinculadas</h2>
                @forelse ($recent as $item)
                    <a href="{{ route('accounts.wallet', [$item->account_id, $item->wallet_id]) }}" wire:navigate
                       class="rounded-xl border border-zinc-200 p-4 transition hover:border-indigo-500 dark:border-zinc-700">
                        <p class="font-medium">{{ $item->label ?? '—' }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ $item->address }}</p>
                        <p class="mt-1 text-xs text-indigo-400">{{ $item->account_name }}</p>
                    </a>
                @empty
                    <p class="text-sm text-zinc-500">Nenhuma carteira vinculada ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>