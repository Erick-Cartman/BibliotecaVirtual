<?php

use App\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/catalogo', 'pages::catalog')->name('catalog');
    Route::livewire('/circulacion', 'pages::circulation')
        ->middleware('role:librarian,admin')
        ->name('circulation');
    Route::livewire('/usuarios', 'pages::users')
        ->middleware('role:admin')
        ->name('users');
});
