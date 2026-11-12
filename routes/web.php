<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\OperatorController;
use App\Http\Controllers\Web\PublicTrackingController;
use App\Http\Controllers\Web\ShipmentWebController;
use Illuminate\Support\Facades\Route;

// Public KilatLogix Express Tracking Landing Page
Route::get('/', [PublicTrackingController::class, 'index'])->name('home');
Route::get('/track', [PublicTrackingController::class, 'index'])->name('public.track');
Route::get('/track/weather', [PublicTrackingController::class, 'weather'])->name('public.track.weather');

// Session Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Role 1: Administrator Workspace
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    Route::get('/carriers', [AdminController::class, 'carriers'])->name('carriers');
    Route::post('/carriers', [AdminController::class, 'storeCarrier'])->name('carriers.store');
    Route::put('/carriers/{carrier}', [AdminController::class, 'updateCarrier'])->name('carriers.update');
    Route::delete('/carriers/{carrier}', [AdminController::class, 'destroyCarrier'])->name('carriers.destroy');

    Route::get('/locations', [AdminController::class, 'locations'])->name('locations');
    Route::post('/locations', [AdminController::class, 'storeLocation'])->name('locations.store');
    Route::delete('/locations/{location}', [AdminController::class, 'destroyLocation'])->name('locations.destroy');
    Route::get('/locations/geocode', [AdminController::class, 'geocode'])->name('locations.geocode');

    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});

// Role 2: Logistics Operator / Staff Workspace
Route::prefix('operator')->name('operator.')->middleware(['auth', 'role:operator,admin'])->group(function () {
    Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('dashboard');
});

// Role 3: Customer Portal
Route::prefix('customer')->name('customer.')->middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
    Route::get('/notifications', [CustomerController::class, 'notifications'])->name('notifications');
    Route::patch('/notifications/{notification}/read', [CustomerController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [CustomerController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
});

// Shared Policy-Guarded Shipment Views & Operations
Route::middleware('auth')->group(function () {
    Route::get('/shipments', [ShipmentWebController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentWebController::class, 'create'])->middleware('role:admin,operator')->name('shipments.create');
    Route::post('/shipments', [ShipmentWebController::class, 'store'])->middleware('role:admin,operator')->name('shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentWebController::class, 'show'])->name('shipments.show');
    Route::put('/shipments/{shipment}', [ShipmentWebController::class, 'update'])->middleware('role:admin,operator')->name('shipments.update');
    Route::delete('/shipments/{shipment}', [ShipmentWebController::class, 'destroy'])->middleware('role:admin,operator')->name('shipments.destroy');

    Route::post('/shipments/{shipment}/tracking', [ShipmentWebController::class, 'addTrackingEvent'])->middleware('role:admin,operator')->name('shipments.tracking.store');
    Route::post('/shipments/{shipment}/exceptions', [ShipmentWebController::class, 'openException'])->middleware('role:admin,operator')->name('shipments.exceptions.store');
    Route::patch('/exceptions/{exception}', [ShipmentWebController::class, 'updateException'])->middleware('role:admin,operator')->name('exceptions.update');
    Route::get('/web/shipments/{shipment}/weather', [ShipmentWebController::class, 'weather'])->name('web.shipments.weather');
});
