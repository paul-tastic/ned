<?php

use App\Models\Server;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('servers/create', 'servers.create')->name('servers.create');
    Route::get('servers/{server}', fn (Server $server) => view('servers.show', ['server' => $server]))->name('servers.show');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
