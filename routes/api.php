<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentificate\AuthController;
use App\Http\Controllers\Authentificate\PasswordResetController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Leave\LeaveController;


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});
Route::post('password/email', [PasswordResetController::class, 'sendResetLink']);
Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);


//route for admin and hr
Route::middleware(['auth:api', 'role:admin,hr'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:api', 'role:employee,hr'])->group(function () {
    Route::post('user/leaves', [LeaveController::class, 'store']);
});