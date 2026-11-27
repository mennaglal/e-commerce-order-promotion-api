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

// Public products
Route::prefix('products')
    ->middleware('throttle:api-public')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show')->whereNumber('id');
    });

// Authenticated customer routes
Route::middleware(['auth:sanctum', 'throttle:api-auth'])
    ->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);

        // Cart
        Route::prefix('cart')
            ->controller(CartController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/items', 'store');
                Route::patch('/items/{itemId}', 'update')->whereNumber('itemId');
                Route::delete('/items/{itemId}', 'destroy')->whereNumber('itemId');
            });

        // Checkout
        Route::post('/checkout', [CheckoutController::class, 'createOrder'])
            ->middleware('throttle:checkout');

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);

        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->whereNumber('order');

        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])
            ->whereNumber('order');
    });
