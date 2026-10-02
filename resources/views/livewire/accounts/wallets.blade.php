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
            $this->addError('address', 'This wallet is already linked to this account');

            return;
        }

        $account->wallets()->attach($wallet->id, ['label' => $validated['label'] ?: null]);

        $this->reset(['address', 'label']);
        unset($this->wallets);
    }

    // RN-09b: desvincular só remove o vínculo desta conta.
    public function unlink(int $walletId): void
    {
        $this->account()->wallets()->detach($walletId);

        unset($this->wallets);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
    <h1 class="text-xl font-semibold">Wallets</h1>

    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @foreach ($this->wallets as $wallet)
            <li class="flex items-center justify-between py-3">
                <div>
    <p class="font-medium">{{ $wallet->pivot->label ?? '—' }}</p>
    <a href="{{ route('accounts.wallet', [$accountId, $wallet]) }}" wire:navigate class="break-all text-sm text-zinc-500 underline">{{ $wallet->address }}</a>
</div>
                <flux:button size="sm" wire:click="unlink({{ $wallet->id }})">Unlink</flux:button>
            </li>
        @endforeach
    </ul>

    <form wire:submit="link" class="flex flex-col gap-4">
        <flux:input wire:model="address" label="Solana address" type="text" required />
        <flux:input wire:model="label" label="Label (optional)" type="text" />
        <flux:button type="submit" variant="primary">Link wallet</flux:button>
    </form>
</div>