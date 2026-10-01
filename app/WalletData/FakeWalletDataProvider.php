<?php

namespace App\WalletData;

use App\WalletData\Dto\WalletBalance;
use App\WalletData\Exceptions\ProviderUnavailable;

/** Reads JSON fixtures instead of calling the API. Used locally and in tests. */
final class FakeWalletDataProvider implements WalletDataProvider
{
    public function __construct(
        private readonly SolscanMapper $mapper,
        private readonly string $path,
    ) {}

    public function balance(string $address, bool $fresh = false): WalletBalance
    {
        return $this->mapper->balance($this->read('account-detail.json'));
    }

    public function tokens(string $address, bool $fresh = false): array
    {
        return $this->mapper->tokens($this->read('token-accounts.json'));
    }

    public function transactions(string $address, ?string $before = null, int $limit = 20, bool $fresh = false): array
    {
        $first = $this->mapper->transactions($this->read('transactions.json'));

        if ($before === null) {
            return $first;
        }

        // The cursor is the signature of the last transaction of the previous page.
        if ($first !== [] && $before === end($first)->hash) {
            return $this->mapper->transactions($this->read('transactions-page-2.json'));
        }

        return [];
    }

    private function read(string $file): string
    {
        $content = @file_get_contents($this->path.'/'.$file);

        if ($content === false) {
            throw new ProviderUnavailable("Fixture {$file} not found.");
        }

        return $content;
    }
}
