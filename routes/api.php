<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Cliente\OrderController as ClienteOrderController;
use App\Http\Controllers\Api\Mensajero\OrderController as MensajeroOrderController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'registerCliente']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// --- Rutas por rol (protegidas) ---
Route::middleware(['auth:sanctum', 'role:cliente'])->prefix('cliente')->group(function () {
    Route::get('orders', [ClienteOrderController::class, 'index']);
    Route::post('orders', [ClienteOrderController::class, 'store']);
    Route::get('orders/{order}', [ClienteOrderController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:mensajero'])->prefix('mensajero')->group(function () {
    Route::get('orders', [MensajeroOrderController::class, 'index']);
    Route::get('orders/{order}', [MensajeroOrderController::class, 'show']);
    Route::post('orders/{order}/start-pickup', [MensajeroOrderController::class, 'startPickup']);
    Route::post('orders/{order}/confirm-pickup', [MensajeroOrderController::class, 'confirmPickup']);
    Route::post('orders/{order}/start-delivery', [MensajeroOrderController::class, 'startDelivery']);
    Route::post('orders/{order}/confirm-delivery', [MensajeroOrderController::class, 'confirmDelivery']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);
    Route::post('orders/{order}/assign', [AdminOrderController::class, 'assign']);
});