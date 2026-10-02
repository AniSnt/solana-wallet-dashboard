<?php

use App\Enums\AccountType;
use App\Rules\Cnpj;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $cnpj = '';

    #[Computed]
    public function accounts(): Collection
    {
        // Sempre parte do usuário logado, nunca de Account::all() (RN-09).
        return auth()->user()->accounts()->withCount('wallets')->orderByDesc('type')->orderBy('name')->get();
    }

    public function createCompany(): void
    {
        // RN-04: normaliza ANTES de validar, para o unique comparar só dígitos.
        $this->cnpj = preg_replace('/\D/', '', $this->cnpj);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', new Cnpj, 'unique:accounts,document'],
        ]);

        // O user_id nunca vem do formulário: a conta nasce do usuário logado (RN-09).
        auth()->user()->accounts()->create([
            'type' => AccountType::Company,
            'name' => $validated['name'],
            'document' => $validated['cnpj'],
        ]);

        $this->reset(['name', 'cnpj']);
        unset($this->accounts);
    }
}; ?>

<div class="page">
            <div class="page-heading">
        <h1 class="text-2xl font-semibold">Contas</h1>
        <p class="text-sm text-zinc-500">Cada conta tem suas próprias wallets Solana.</p>
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($this->accounts as $account)
            <div class="flex items-center justify-between rounded-xl border border-zinc-200 p-4 transition hover:border-indigo-500 dark:border-zinc-700">
                <div class="flex items-center gap-4">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $account->type === AccountType::Individual ? 'bg-emerald-500/10 text-emerald-400' : 'bg-indigo-500/10 text-indigo-400' }}">
                        {{ $account->type === AccountType::Individual ? 'PF' : 'PJ' }}
                    </span>
                    <div>
                        <p class="font-medium">{{ $account->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $account->maskedDocument() }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-zinc-500">{{ $account->wallets_count }} {{ $account->wallets_count === 1 ? 'wallet' : 'wallets' }}</span>
                    <flux:button size="sm" variant="primary" href="{{ route('accounts.wallets', $account) }}" wire:navigate>Wallets</flux:button>
                </div>
            </div>
        @endforeach
    </div>

    <form wire:submit="createCompany" class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <div class="page-heading">
            <h2 class="font-semibold">Nova conta PJ</h2>
            <p class="text-sm text-zinc-500">Informe a razão social e o CNPJ, com ou sem máscara.</p>
        </div>

        <flux:input wire:model="name" label="Razão social" type="text" required />
        <flux:input wire:model="cnpj" label="CNPJ" type="text" inputmode="numeric" placeholder="00.000.000/0000-00" required />

                <div class="flex justify-center">
            <flux:button type="submit" variant="primary">Criar conta PJ</flux:button>
        </div>
    </form>
</div>