<?php

namespace App\WalletData\Exceptions;

/** Timeout, 429, 5xx or an unreadable response: try again later. */
class ProviderUnavailable extends WalletDataException {}
