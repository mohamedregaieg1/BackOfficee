<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authController;
use App\Http\Controllers\PasswordResetController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [authController::class, 'login']);
    Route::post('logout', [authController::class, 'logout']);
    Route::post('refresh', [authController::class, 'refresh']);
    Route::post('me', [authController::class, 'me']);

});
Route::post('password/email', [PasswordResetController::class, 'sendResetLink']);
Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);