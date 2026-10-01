<?php

namespace App\WalletData;

use App\WalletData\Dto\TokenBalance;
use App\WalletData\Dto\WalletBalance;
use App\WalletData\Dto\WalletTransaction;
use App\WalletData\Exceptions\ProviderUnavailable;

/** Turns raw Solscan JSON into our DTOs. The rest of the app never sees the raw array. */
final class SolscanMapper
{
    public function balance(string $json): WalletBalance
    {
        $data = $this->data($json);
        $lamports = (string) ($data['lamports'] ?? '0');

        return new WalletBalance($lamports, Units::sol($lamports));
    }

    /** @return list<TokenBalance> */
    public function tokens(string $json): array
    {
        return array_map(function (array $row): TokenBalance {
            // amount_str, never amount: the number can exceed PHP_INT_MAX.
            $raw = (string) $row['amount_str'];
            $decimals = (int) $row['token_decimals'];

            return new TokenBalance(
                (string) $row['token_account'],
                (string) $row['token_address'],
                $raw,
                $decimals,
                Units::toDecimal($raw, $decimals),
            );
        }, array_values($this->data($json)));
    }

    /** @return list<WalletTransaction> */
    public function transactions(string $json): array
    {
        return array_map(fn (array $row): WalletTransaction => new WalletTransaction(
            (string) $row['tx_hash'],
            (string) $row['status'],
            (int) $row['block_time'],
            (string) ($row['fee'] ?? '0'),
            array_values(array_map('strval', $row['signer'] ?? [])),
        ), array_values($this->data($json)));
    }

    /** @return array<mixed> */
    private function data(string $json): array
    {
        // JSON_BIGINT_AS_STRING keeps huge integers exact instead of turning them into float.
        $decoded = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);

        if (! is_array($decoded) || ($decoded['success'] ?? false) !== true || ! array_key_exists('data', $decoded)) {
            throw new ProviderUnavailable('Unexpected response from the data provider.');
        }

        return $decoded['data'];
    }
}
