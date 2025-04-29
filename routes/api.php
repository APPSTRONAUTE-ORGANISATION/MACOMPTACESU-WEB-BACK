<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategorieController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseLabelController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VacationWeekController;
use App\Http\Controllers\WeeklyBudgetController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/me', function (Request $request) {
    return new UserResource($request->user());
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'Login']);
Route::post('logout', [AuthController::class, 'Logout']);
Route::post('register', [AuthController::class, 'Register']);

Route::post('forgot_password', [AuthController::class, 'ForgotPassword']);
Route::post('check_reset_password_token', [AuthController::class, 'CheckResetPasswordToken']);
Route::post('reset_password', [AuthController::class, 'ResetPassword']);

Route::get('plans', [PlanController::class, 'index']);

Route::group(['middleware' => 'role:admin'], function () {
    Route::get('users/export_excel', [AuthController::class, 'ExportExcel']);
    Route::get('users', [AuthController::class, 'index']);
    Route::get('users/{user}', [AuthController::class, 'show']);
    Route::post('users/{user}', [AuthController::class, 'update']);
    Route::delete('users/{user}', [AuthController::class, 'destroy']);
});

Route::post('update_profile_image', [AuthController::class, 'UpdateProfileImage']);
Route::post('/subscription/free-trial', [StripeController::class, 'createFreeTrial']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('update_password', [AuthController::class, 'UpdatePassword']);
    Route::post('update_profile', [AuthController::class, 'UpdateProfile']);
    // Route::post('update_profile_image', [AuthController::class, 'UpdateProfileImage']);

    Route::post('create_client_secret', [StripeController::class, 'CreateClientSecret']);

    Route::apiResource('activities', ActivityController::class);

    Route::get('clients/total_clients', [ClientController::class, 'TotalClients']);
    Route::apiResource('clients', ClientController::class);

    Route::get('invoices/calendar', [InvoiceController::class, 'Calendar']);
    Route::get('invoices/due_invoices', [InvoiceController::class, 'DueInvoices']);
    Route::get('invoices/invoices_by_month', [InvoiceController::class, 'getTotalInvoicesByMonth']);
    Route::get('invoices/invoices_by_year', [InvoiceController::class, 'getInvoicesByYear']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::put('invoice_days/{invoice_day}', [InvoiceController::class, 'UpdateInvoiceDay']);
    Route::post('invoice_days', [InvoiceController::class, 'StoreInvoiceDay']);
    Route::delete('invoice_days/{invoice_day}', [InvoiceController::class, 'DestroyInvoiceDay']);

    Route::apiResource('payments', PaymentController::class);

    Route::get('subscriptions', [StripeController::class, 'Subscriptions']);
    Route::get('subscriptions/{subscription}/cancel', [StripeController::class, 'CancelSubscriptions']);
    Route::get('stripe/card-details', [StripeController::class, 'getUserCardInfo']);
    // Route::post('/subscription/free-trial', [StripeController::class, 'createFreeTrial']);

    Route::apiResource('tickets', TicketController::class);

    Route::prefix('stats')->group(function () {
        Route::get('work_hours', [DashboardController::class, 'WorkHours']);
        Route::get('invoices', [DashboardController::class, 'Invoices']);
        Route::get('unpaid_invoices', [DashboardController::class, 'UnpaidInvoices']);
        Route::get('latest_payments', [DashboardController::class, 'LatestPayments']);
        Route::get('latest_unpaid_clients', [DashboardController::class, 'LatestUnpaidClients']);
        Route::get('clients', [DashboardController::class, 'Clients']);
    });

    Route::apiResource('expense_categories', ExpenseCategorieController::class);
    Route::apiResource('expense_labels', ExpenseLabelController::class);
    Route::apiResource('expenses', ExpenseController::class);

    Route::apiResource('vacation_weeks', VacationWeekController::class);

    // Route::apiResource('weekly_budgets', WeeklyBudgetController::class);
    Route::apiResource('budgets', BudgetController::class);

    Route::get('graph', [DashboardController::class, 'Graph']);
    Route::get('weeks_comparison', [DashboardController::class, 'WeeksComparison']);
    Route::get('accounts', [DashboardController::class, 'Accounts']);
});
