<?php

namespace App\WalletData\Exceptions;

/** Missing or rejected API key (HTTP 401). The user never sees the details. */
class ProviderMisconfigured extends WalletDataException {}
