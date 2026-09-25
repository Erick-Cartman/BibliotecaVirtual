<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Models\Book;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featuredBooks = Book::query()
        ->where('active', true)
        ->whereNotNull('cover_url')
        ->orderBy('title')
        ->limit(10)
        ->get();

    return view('welcome', compact('featuredBooks'));
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
