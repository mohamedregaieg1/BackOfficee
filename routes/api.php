<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentificate\AuthController;
use App\Http\Controllers\Authentificate\PasswordResetController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveBalanceController;
use App\Http\Controllers\Employee\ProfileController;



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

//route for admin 
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/users', [LeaveBalanceController::class, 'index']);
    Route::post('/leave-balances/{userId}', [LeaveBalanceController::class, 'store']);
    Route::get('/leave-balances/{userId}', [LeaveBalanceController::class, 'show']);
    Route::delete('/leave-balances/{id}', [LeaveBalanceController::class, 'destroy']);
});


//route for admin and hr
Route::middleware(['auth:api', 'role:admin,hr'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);

});


//route for employee and hr
Route::middleware(['auth:api', 'role:employee,hr'])->group(function () {
    Route::post('user/leaves', [LeaveController::class, 'store']);
    Route::get('/profile', [ProfileController::class, 'show']);
});
