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
        return auth()->user()->accounts()->orderByDesc('type')->orderBy('name')->get();
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

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
    <h1 class="text-xl font-semibold">Contas</h1>

    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @foreach ($this->accounts as $account)
            <li class="flex items-center justify-between py-3">
                <div>
                    <p class="font-medium">{{ $account->name }}</p>
                    <p class="text-sm text-zinc-500">
                        {{ $account->type === AccountType::Individual ? 'PF' : 'PJ' }}
                        · {{ $account->maskedDocument() }}
                    </p>
                </div>
                <a href="{{ route('accounts.wallets', $account) }}" wire:navigate class="text-sm underline">Wallets</a>
            </li>
        @endforeach
    </ul>

    <form wire:submit="createCompany" class="flex flex-col gap-4">
        <flux:input wire:model="name" label="Razão social" type="text" required />
        <flux:input wire:model="cnpj" label="CNPJ" type="text" inputmode="numeric" placeholder="00.000.000/0000-00" required />
        <flux:button type="submit" variant="primary">Criar conta PJ</flux:button>
    </form>
</div>