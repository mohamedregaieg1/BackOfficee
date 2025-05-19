<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentificate\AuthController;
use App\Http\Controllers\Authentificate\PasswordResetController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\SendEmailController;
use App\Http\Controllers\Admin\HistoriqueInvoiceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceDashboardController;
use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveBalanceController;
use App\Http\Controllers\Leave\ViewLeaveController;
use App\Http\Controllers\Leave\FixedLeavesController;
use App\Http\Controllers\Leave\PublicHolidayController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\HomeEmployeeController;
use App\Http\Controllers\Accountant\ClientController;
use App\Http\Controllers\NotificationController;

// Authentication routes
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login'])->name('login'); // User login
    Route::post('logout', [AuthController::class, 'logout']); // User logout
    Route::post('refresh', [AuthController::class, 'refresh']); // Refresh JWT token
    Route::post('me', [AuthController::class, 'me']); // Get authenticated user info
});

Route::post('password/email', [PasswordResetController::class, 'sendResetLink']); // Send password reset email
Route::post('password/reset', [PasswordResetController::class, 'resetPassword']); // Handle password reset

// Routes for admin
Route::middleware(['auth:api', 'check.token.version', 'role:admin'])->group(function () {
    Route::apiResource('public-holidays', PublicHolidayController::class); // CRUD for public holidays
    Route::get('/by-name', [CompanyController::class, 'showByName']); // Get company by name
    Route::post('/companies', [CompanyController::class, 'store']); // Create new company
    Route::put('/{id}', [CompanyController::class, 'update']); // Update company info
    Route::post('invoices/step-one', [InvoiceController::class, 'stepOne']); // Invoice creation - step 1
    Route::get('/clients', [InvoiceController::class, 'getAllClients']); // Get all clients
    Route::get('/clients/{id}', [InvoiceController::class, 'getClientById']); // Get single client information
    Route::post('invoices/step-two', [InvoiceController::class, 'stepTwo']); // Invoice creation - step 2
    Route::post('invoices/step-three', [InvoiceController::class, 'stepThree']); // Invoice creation - step 3
    Route::post('invoices/store', [InvoiceController::class, 'store']); // Store full invoice
    Route::get('/invoices/{id}/send-email', [SendEmailController::class, 'sendEmail']); // Send invoice via email
});

// Routes for admin and HR
Route::middleware(['auth:api', 'check.token.version', 'role:admin,hr'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']); // List all users
    Route::post('/admin/users', [UserController::class, 'store']); // Create new user
    Route::put('/admin/users/{id}', [UserController::class, 'update']); // Update user
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']); // Delete user
    Route::get('/users', [LeaveBalanceController::class, 'index']); // Get users with leave balances
    Route::post('/leave-balances/{userId}', [LeaveBalanceController::class, 'store']); // Create leave balance
    Route::get('/leave-balances/{userId}', [LeaveBalanceController::class, 'show']); // Get leave balance by user
    Route::delete('/leave-balances/{id}', [LeaveBalanceController::class, 'destroy']); // Delete leave balance
    Route::patch('/admin/leaves/{leaveId}/status', [ViewLeaveController::class, 'updateStatus']); // Update leave status
    Route::get('/admin/employees/{userId}/leaves', [ViewLeaveController::class, 'showLeavesForAdmin']); // Show user leaves
    Route::put('/leave/{leaveId}/update', [ViewLeaveController::class, 'updateLeaveForAdmin']); // Update specific leave
    Route::get('/leaves/{id}', [LeaveController::class, 'show'])->name('leaves.show'); // Show leave detail
    Route::apiResource('leave-limits', FixedLeavesController::class); // Manage fixed leave types

    // Dashboard routes for admin/HR
    Route::get('/dashboard/count/approved', [DashboardController::class, 'countApprovedLeavesThisMonth']); // Approved leaves count
    Route::get('/dashboard/count/rejected', [DashboardController::class, 'countRejectedLeavesThisMonth']); // Rejected leaves count
    Route::get('/dashboard/count/on-hold', [DashboardController::class, 'countOnHoldLeavesThisMonth']); // On-hold leaves count
    Route::get('/dashboard/leaves/today', [DashboardController::class, 'getLeavesToday']); // Today's leaves
    Route::get('/dashboard/leave-type-distribution', [DashboardController::class, 'leaveTypeDistribution']); // Leave type stats
    Route::get('/dashboard/leave-status-distribution', [DashboardController::class, 'leaveStatusDistribution']); // Leave status stats
    Route::get('/dashboard/approved-leaves-by-employee', [DashboardController::class, 'approvedLeavesByEmployee']); // Leaves per employee
    Route::get('/dashboard/compare-leaves-by-year', [DashboardController::class, 'compareApprovedLeavesByYear']); // Yearly leave comparison
});

// Routes for employees and HR
Route::middleware(['auth:api', 'check.token.version', 'role:employee,hr'])->group(function () {
    Route::post('leave/calculate', [LeaveController::class, 'calculateLeaveDays']); // Calculate leave duration
    Route::post('leave/store', [LeaveController::class, 'store']); // Submit leave request
    Route::get('/leave/{id}/download', [LeaveController::class, 'downloadLeavePdf']); // Download leave PDF
    Route::post('/leaves/notify-rejection', [LeaveController::class, 'notifyHROnRejectedLeave']); // Notify HR on rejection
    Route::get('/employee/leaves', [ViewLeaveController::class, 'showLeavesForEmployee']); // View own leaves
    Route::post('/employee/leaves/{leaveId}', [ViewLeaveController::class, 'updateLeave']); // Update own leave
    Route::delete('/employee/leaves/{leaveId}', [ViewLeaveController::class, 'deleteLeave']); // Delete own leave

    // Home dashboard for employees
    Route::get('/employee/home/leaves-status', [HomeEmployeeController::class, 'leavesByStatus']); // Leave stats by status
    Route::get('/employee/home/leave-balance', [HomeEmployeeController::class, 'leaveBalance']); // Current leave balance
    Route::get('/employee/home/last-leave-addition', [HomeEmployeeController::class, 'lastLeaveAddition']); // Last leave change
    Route::get('/employee/home/calendar', [HomeEmployeeController::class, 'getCalendarData']); // Calendar events
    Route::get('/employee/home/holidays/upcoming', [HomeEmployeeController::class, 'upcomingPublicHolidays']); // Upcoming holidays
});

// Routes for accountant
Route::middleware(['auth:api', 'check.token.version', 'role:accountant'])->group(function () {
    Route::get('/show/clients', [ClientController::class, 'index']); // List clients
    Route::post('/clients/add', [ClientController::class, 'store']); // Add new client
    Route::put('/clients/{id}', [ClientController::class, 'update']); // Update client
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']); // Delete client
});

// Routes for accountant and admin
Route::middleware(['auth:api', 'role:accountant,admin'])->group(function () {
    Route::get('/invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPdf']); // Download invoice PDF
    Route::get('/show/invoices', [HistoriqueInvoiceController::class, 'index']); // List invoices
    Route::put('/invoices/{id}/payment-status', [HistoriqueInvoiceController::class, 'updatePaymentStatus']); // Update payment status
    Route::get('/invoices/{id}/historique', [HistoriqueInvoiceController::class, 'getHistoriqueByInvoiceId']); // Invoice history
    Route::get('/invoices/{id}/services', [HistoriqueInvoiceController::class, 'getServicesByInvoice']); // List services
    Route::put('/invoices/services/batch-update', [HistoriqueInvoiceController::class, 'updateService'])->name('service.update'); // Batch service update
    Route::post('/invoices/transfer-avp', [HistoriqueInvoiceController::class, 'transferAVP']); // Transfer AVP
    Route::get('/payment-status', [InvoiceDashboardController::class, 'paymentStatusStats']); // Payment status stats
    Route::get('/type-stats', [InvoiceDashboardController::class, 'invoiceTypeStats']); // Invoice type stats
    Route::get('/payment-mode', [InvoiceDashboardController::class, 'paymentModeStats']); // Payment mode stats
});

// Shared routes for all authenticated users
Route::middleware(['auth:api', 'check.token.version', 'role:employee,hr,admin,accountant'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']); // List all notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']); // Count of unread notifications
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']); // Mark notification as read
    Route::delete('/notifications/{id}', [NotificationController::class, 'deleteNotification']); // Delete notification
    Route::get('/user/sidebar', [ProfileController::class, 'showsidebar']); // Show user sidebar
    Route::get('/user/profile', [ProfileController::class, 'show']); // Show user profile
    Route::post('/user/profile/update', [ProfileController::class, 'updateProfile']); // Update user profile
    Route::post('/user/profile/update-avatar', [ProfileController::class, 'updateAvatar']); // Update profile picture
    Route::get('home/user/info', [HomeEmployeeController::class, 'getAuthenticatedUserInfo']); // User dashboard info
});
