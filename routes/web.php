<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('accounts', 'accounts.index')->name('accounts.index');
    Volt::route('accounts/{account}/wallets', 'accounts.wallets')->name('accounts.wallets');
    Volt::route('accounts/{account}/wallets/{wallet}', 'accounts.wallet')->name('accounts.wallet');
});

require __DIR__.'/auth.php';
