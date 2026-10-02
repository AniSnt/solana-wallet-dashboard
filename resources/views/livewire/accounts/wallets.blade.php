<?php

use App\Models\Account;
use App\Models\Wallet;
use App\Rules\SolanaAddress;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public int $accountId;

    public string $address = '';
    public string $label = '';
    public string $status = '';

    public function mount(int $account): void
    {
        $this->accountId = $account;
        $this->account(); // 404 imediato se a conta não for do usuário
    }

    // RN-09: sempre parte do usuário logado. Conta alheia vira 404.
    private function account(): Account
    {
        return auth()->user()->accounts()->findOrFail($this->accountId);
    }

    #[Computed]
    public function wallets(): Collection
    {
        return $this->account()->wallets()->orderBy('account_wallet.created_at')->get();
    }

    public function link(): void
    {
        $account = $this->account();

        $validated = $this->validate([
            'address' => ['required', new SolanaAddress],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        // RN-07: reaproveita a carteira existente, sem duplicar.
        $wallet = Wallet::firstOrCreate(['address' => $validated['address']]);

        if ($account->wallets()->whereKey($wallet->id)->exists()) {
            $this->addError('address', 'Esta carteira já está vinculada a esta conta');

            return;
        }

        $account->wallets()->attach($wallet->id, ['label' => $validated['label'] ?: null]);

        $this->reset(['address', 'label']);
        $this->status = 'Carteira vinculada.';
        unset($this->wallets);
    }

    // RN-09b: desvincular só remove o vínculo desta conta.
    public function unlink(int $walletId): void
    {
        $this->account()->wallets()->detach($walletId);

        $this->status = 'Carteira desvinculada.';
        unset($this->wallets);
    }
}; ?>

<div class="page">
    <a href="{{ route('accounts.index') }}" wire:navigate class="text-lg underline" aria-label="Voltar para contas" title="Voltar para contas">&larr;</a>

<h1 class="text-xl font-semibold">Wallets</h1>

    @if ($status)
        <p class="text-sm text-green-500">{{ $status }}</p>
    @endif

    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @foreach ($this->wallets as $wallet)
            <li class="flex items-center justify-between py-3">
                <div>
                    <p class="font-medium">{{ $wallet->pivot->label ?? '—' }}</p>
                    <p class="break-all text-sm text-zinc-500">{{ $wallet->address }}</p>
                </div>
                <div class="flex gap-2">
                    <flux:button size="sm" href="{{ route('accounts.wallet', [$accountId, $wallet]) }}" wire:navigate>Detalhes</flux:button>
                    <flux:button size="sm" wire:click="unlink({{ $wallet->id }})" wire:confirm="Desvincular esta carteira da conta?">Desvincular</flux:button>
                </div>
            </li>
        @endforeach
    </ul>

    <form wire:submit="link" class="flex flex-col gap-4">
        <flux:input wire:model="address" label="Endereço Solana" type="text" required />
        <flux:input wire:model="label" label="Apelido (opcional)" type="text" />
        <flux:button type="submit" variant="primary">Vincular carteira</flux:button>
    </form>
</div>