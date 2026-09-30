<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Account extends Model
{
    protected $fillable = ['user_id', 'type', 'name', 'document'];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallets(): BelongsToMany
    {
        return $this->belongsToMany(Wallet::class)
            ->withPivot('label')
            ->withTimestamps();
    }
}