<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierController;
use App\Http\Controllers\Api\ExceptionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\TrackingController;
use Illuminate\Support\Facades\Route;

// Public Sanctum Auth endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected API endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Shipments & nested Tracking / Exceptions / Weather
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::post('/shipments', [ShipmentController::class, 'store'])->middleware('role:admin,operator');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
    Route::match(['put', 'patch'], '/shipments/{shipment}', [ShipmentController::class, 'update'])->middleware('role:admin,operator');
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->middleware('role:admin,operator');
    Route::get('/shipments/{shipment}/weather', [ShipmentController::class, 'weather']);

    Route::get('/shipments/{shipment}/tracking', [TrackingController::class, 'index']);
    Route::post('/shipments/{shipment}/tracking', [TrackingController::class, 'store'])->middleware('role:admin,operator');

    Route::get('/shipments/{shipment}/exceptions', [ExceptionController::class, 'index']);
    Route::post('/shipments/{shipment}/exceptions', [ExceptionController::class, 'store'])->middleware('role:admin,operator');
    Route::patch('/exceptions/{exception}', [ExceptionController::class, 'update'])->middleware('role:admin,operator');

    // Carriers & Locations
    Route::get('/carriers', [CarrierController::class, 'index']);
    Route::post('/carriers', [CarrierController::class, 'store'])->middleware('role:admin');

    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations', [LocationController::class, 'store'])->middleware('role:admin');
});
