<?php

use App\Enums\AccountType;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function accounts(): Collection
    {
        // Sempre parte do usuário logado, nunca de Account::all() (RN-09).
        return auth()->user()->accounts()->orderByDesc('type')->orderBy('name')->get();
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
                        · {{ $account->document }}
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
</div>