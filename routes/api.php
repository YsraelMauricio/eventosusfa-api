<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::get('/health', HealthController::class);
Route::get('/categories', [CategoryController::class, 'index']);
