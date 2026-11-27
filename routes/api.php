<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:register');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Public Products
Route::prefix('products')
    ->middleware('throttle:api-public')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show')->whereNumber('id');
    });

// Authenticated Routes
Route::middleware(['auth:sanctum', 'throttle:api-auth'])
    ->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);

        // Cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/items', [CartController::class, 'store']);
            Route::patch('/items/{itemId}', [CartController::class, 'update']);
            Route::delete('/items/{itemId}', [CartController::class, 'destroy']);
        });

        // Checkout
        Route::post('/checkout', [CheckoutController::class, 'checkout'])
            ->middleware('throttle:checkout');

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->whereNumber('order');

        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->whereNumber('order');
    });
