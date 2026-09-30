<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Models\Book;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $defaultBook = Book::query()
        ->where('source_key', 'demo-el-principito')
        ->where('active', true)
        ->first();

    $featuredBooks = Book::query()
        ->where('active', true)
        ->whereNotNull('cover_url')
        ->where('cover_url', '!=', '')
        ->where('source_key', '!=', 'demo-el-principito')
        ->orderBy('title')
        ->limit(9)
        ->get();

    if ($defaultBook) {
        $featuredBooks = collect([$defaultBook])->concat($featuredBooks);
    }

    if ($featuredBooks->isEmpty()) {
        $featuredBooks = Book::query()
            ->where('active', true)
            ->orderBy('title')
            ->limit(10)
            ->get();
    }

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
    Route::post('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
    Route::livewire('/catalogo', 'pages::catalog')->name('catalog');
    Route::livewire('/circulacion', 'pages::circulation')
        ->middleware('role:librarian,admin')
        ->name('circulation');
    Route::livewire('/usuarios', 'pages::users')
        ->middleware('role:admin')
        ->name('users');
});
