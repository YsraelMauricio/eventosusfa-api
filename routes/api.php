<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::get('/health', HealthController::class);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);

// Rutas protegidas con token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me/events', [EventController::class, 'mine']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
});
