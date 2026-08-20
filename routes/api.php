<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Cliente\OrderController as ClienteOrderController;
use App\Http\Controllers\Api\Mensajero\OrderController as MensajeroOrderController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Mensajero\ShiftController;
use App\Http\Controllers\Api\Mensajero\LocationController;
use App\Http\Controllers\Api\Admin\CourierController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\Mensajero\ReportController as MensajeroReportController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\CourierManagementController;
use App\Http\Controllers\Api\Admin\ClinicManagementController;
use App\Http\Controllers\Api\Cliente\PaymentController;
use App\Http\Controllers\Api\Admin\AssignmentSettingController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\Cliente\ProfileController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'registerCliente']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// --- Recursos compartidos entre los 3 roles ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
    Route::post('broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });
    Route::put('password', [PasswordController::class, 'update']);
});

// --- Rutas por rol (protegidas) ---
Route::middleware(['auth:sanctum', 'role:cliente'])->prefix('cliente')->group(function () {
    Route::get('orders', [ClienteOrderController::class, 'index']);
    Route::post('orders', [ClienteOrderController::class, 'store']);
    Route::get('orders/{order}', [ClienteOrderController::class, 'show']);

    Route::get('orders/{order}/payment', [PaymentController::class, 'show']);
    Route::post('orders/{order}/pay', [PaymentController::class, 'pay']);

    Route::put('profile', [ProfileController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'role:mensajero'])->prefix('mensajero')->group(function () {
    Route::get('orders', [MensajeroOrderController::class, 'index']);
    Route::get('orders/{order}', [MensajeroOrderController::class, 'show']);
    Route::post('orders/{order}/start-pickup', [MensajeroOrderController::class, 'startPickup']);
    Route::post('orders/{order}/confirm-pickup', [MensajeroOrderController::class, 'confirmPickup']);
    Route::post('orders/{order}/start-delivery', [MensajeroOrderController::class, 'startDelivery']);
    Route::post('orders/{order}/confirm-delivery', [MensajeroOrderController::class, 'confirmDelivery']);

    Route::get('shifts/current', [ShiftController::class, 'current']);
    Route::post('shifts/start', [ShiftController::class, 'start']);
    Route::post('shifts/end', [ShiftController::class, 'end']);

    Route::post('locations', [LocationController::class, 'store']);

    Route::get('reports/monthly', [MensajeroReportController::class, 'monthly']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);
    Route::post('orders/{order}/assign', [AdminOrderController::class, 'assign']);

    Route::get('couriers/on-duty', [CourierController::class, 'onDuty']);
    Route::get('couriers/{courier}/shifts', [CourierController::class, 'shifts']);
    Route::get('shifts/{shift}/locations', [CourierController::class, 'shiftLocations']);

    Route::get('reports/orders', [AdminReportController::class, 'orders']);
    Route::get('reports/couriers-summary', [AdminReportController::class, 'couriersSummary']);

    Route::get('users/couriers', [CourierManagementController::class, 'index']);
    Route::post('users/couriers', [CourierManagementController::class, 'store']);
    Route::put('users/couriers/{courier}', [CourierManagementController::class, 'update']);
    Route::patch('users/couriers/{courier}/status', [CourierManagementController::class, 'updateStatus']);

    Route::get('users/clinics', [ClinicManagementController::class, 'index']);
    Route::put('users/clinics/{clinic}', [ClinicManagementController::class, 'update']);
    Route::patch('users/clinics/{clinic}/status', [ClinicManagementController::class, 'updateStatus']);

    Route::get('assignment-settings', [AssignmentSettingController::class, 'show']);
    Route::put('assignment-settings', [AssignmentSettingController::class, 'update']);
});