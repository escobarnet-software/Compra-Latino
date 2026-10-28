<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiDashboardController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\YAuctionsFakeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');

    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [StoreController::class, 'index'])->name('store.index');

    Route::get('/productos/{product}', [StoreController::class, 'show'])
        ->name('store.show');

    Route::get('/checkout', [StoreController::class, 'checkout'])
        ->name('store.checkout');
    Route::post('/ordenes', [StoreController::class, 'store'])
        ->name('orders.store');

    Route::get('/mis-ordenes', [StoreController::class, 'myOrders'])
        ->name('orders.index');

    Route::get('/para-ti', [StoreController::class, 'recommendations'])
        ->name('store.recommendations');

    Route::get('/ordenes/{ordenId}', [StoreController::class, 'showOrder'])
        ->whereNumber('ordenId')
        ->name('orders.show');
});

Route::get('/simulador-yauctions/catalogo', [YAuctionsFakeController::class, 'catalogo'])
    ->name('yauctions.catalogo');
Route::post('/simulador-yauctions/comprar', [YAuctionsFakeController::class, 'comprar'])
    ->name('yauctions.comprar');

Route::middleware(['auth', 'can:administrador'])->prefix('admin')->name('bi.')->group(function () {
    Route::get('/dashboard', [BiDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/auditoria', [BiDashboardController::class, 'auditoria'])
        ->name('auditoria');
});
