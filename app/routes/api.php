<?php

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public authentication endpoints
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

/*
|--------------------------------------------------------------------------
| Authenticated endpoints (Sanctum bearer token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('api-keys', ApiKeyController::class)
        ->parameters(['api-keys' => 'apiKey']);
});
