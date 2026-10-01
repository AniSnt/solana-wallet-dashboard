<?php

namespace App\WalletData\Dto;

final readonly class WalletBalance
{
    /** @param string $lamports raw integer amount; $sol decimal string, never a float */
    public function __construct(
        public string $lamports,
        public string $sol,
    ) {}
}
