<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CapacitorDataController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'throttle:api']);

// Capacitor Device API Routes
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('device')->group(function () {
    Route::post('/location', [CapacitorDataController::class, 'getLocation']);
    Route::post('/barcode', [CapacitorDataController::class, 'saveBarcodeData']);
    Route::post('/photo', [CapacitorDataController::class, 'uploadPhoto']);
    Route::get('/permissions', [CapacitorDataController::class, 'getPermissionsStatus']);
});
