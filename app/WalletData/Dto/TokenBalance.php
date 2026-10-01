<?php

namespace App\WalletData\Dto;

final readonly class TokenBalance
{
    public function __construct(
        public string $tokenAccount,
        public string $tokenAddress,
        public string $rawAmount,
        public int $decimals,
        public string $amount,
    ) {}
}
