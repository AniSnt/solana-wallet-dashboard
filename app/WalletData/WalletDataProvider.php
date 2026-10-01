<?php

namespace App\WalletData;

use App\WalletData\Dto\TokenBalance;
use App\WalletData\Dto\WalletBalance;
use App\WalletData\Dto\WalletTransaction;

interface WalletDataProvider
{
    public function balance(string $address, bool $fresh = false): WalletBalance;

    /** @return list<TokenBalance> */
    public function tokens(string $address, bool $fresh = false): array;

    /**
     * @param  string|null  $before  signature of the last transaction of the previous page
     * @return list<WalletTransaction>
     */
    public function transactions(string $address, ?string $before = null, int $limit = 20, bool $fresh = false): array;
}
