<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentificate\AuthController;
use App\Http\Controllers\Authentificate\PasswordResetController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\SendEmailController;
use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveBalanceController;
use App\Http\Controllers\Leave\ViewLeaveController;
use App\Http\Controllers\Leave\FixedLeavesController;
use App\Http\Controllers\Leave\PublicHolidayController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Accountant\ClientController;
use App\Http\Controllers\NotificationController;






Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login'])->name('login');;
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});
Route::post('password/email', [PasswordResetController::class, 'sendResetLink']);
Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);

//route for admin
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::apiResource('leave-limits', FixedLeavesController::class);
    Route::apiResource('public-holidays', PublicHolidayController::class);
    Route::get('/by-name', [CompanyController::class, 'showByName']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::patch('/admin/leaves/{leaveId}/status', [ViewLeaveController::class, 'updateStatus']);
    Route::post('invoices/step-one', [InvoiceController::class, 'stepOne']);
    Route::get('/clients', [InvoiceController::class, 'getAllClients']);
    Route::get('/clients/{id}', [InvoiceController::class, 'getClientById']);
    Route::post('invoices/step-two', [InvoiceController::class, 'stepTwo']);
    Route::post('invoices/step-three', [InvoiceController::class, 'stepThree']);
    Route::post('invoices/store', [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}/send-email', [SendEmailController::class, 'sendEmail']);

});


//route for admin and hr
Route::middleware(['auth:api', 'role:admin,hr'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
    Route::get('/users', [LeaveBalanceController::class, 'index']);
    Route::post('/leave-balances/{userId}', [LeaveBalanceController::class, 'store']);
    Route::get('/leave-balances/{userId}', [LeaveBalanceController::class, 'show']);
    Route::delete('/leave-balances/{id}', [LeaveBalanceController::class, 'destroy']);
    Route::get('/admin/employees/{userId}/leaves', [ViewLeaveController::class, 'showLeavesForAdmin']);
    Route::put('/leave/{leaveId}/update', [ViewLeaveController::class, 'updateLeaveForAdmin']);
    Route::get('/leaves/{id}', [LeaveController::class, 'show'])->name('leaves.show');




});


//route for employee and hr
Route::middleware(['auth:api', 'role:employee,hr'])->group(function () {
    Route::post('leave/calculate', [LeaveController::class, 'calculateLeaveDays']);
    Route::post('leave/store', [LeaveController::class, 'store']);
    Route::get('/leave/{id}/download', [LeaveController::class, 'downloadLeavePdf']);
    Route::post('/leaves/notify-rejection', [LeaveController::class, 'notifyHROnRejectedLeave']);
    Route::get('/employee/leaves', [ViewLeaveController::class, 'showLeavesForEmployee']);
    Route::post('/employee/leaves/{leaveId}', [ViewLeaveController::class, 'updateLeave']);
    Route::delete('/employee/leaves/{leaveId}', [ViewLeaveController::class, 'deleteLeave']);


});


//route for accountant :

    Route::middleware(['auth:api', 'role:accountant'])->group(function () {
    Route::get('/show/clients', [ClientController::class, 'index']);
    Route::post('/clients/add', [ClientController::class, 'store']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
});

Route::middleware(['auth:api', 'role:accountant,admin'])->group(function () {
    Route::get('/invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPdf']);

});
Route::middleware(['auth:api', 'role:employee,hr,admin,accountant'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'deleteNotification']);
    Route::get('/user/sidebar', [ProfileController::class, 'showsidebar']);
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::post('/user/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/user/profile/update-avatar', [ProfileController::class, 'updateAvatar']);

});
