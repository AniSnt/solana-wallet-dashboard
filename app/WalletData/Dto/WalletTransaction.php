<?php

namespace App\WalletData\Dto;

final readonly class WalletTransaction
{
    /** @param list<string> $signers */
    public function __construct(
        public string $hash,
        public string $status,
        public int $blockTime,
        public string $fee,
        public array $signers,
    ) {}
}
