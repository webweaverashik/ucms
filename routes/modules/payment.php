<?php

use App\Http\Controllers\AutoInvoiceController;
use App\Http\Controllers\Cost\CostController;
use App\Http\Controllers\Payment\PaymentInvoiceCommentController;
use App\Http\Controllers\Payment\PaymentInvoiceController;
use App\Http\Controllers\Payment\PaymentTransactionController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Settlement\SettlementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
| Invoices, transactions, settlements, costs
| ⚠️ IMPORTANT: Route names are kept EXACTLY as original web.php
*/

// Invoices
Route::get('students/{student}/due-invoices', [PaymentInvoiceController::class, 'getDueInvoices'])->name('students.due.invoices');
Route::get('invoices/{invoice}/view-ajax', [PaymentInvoiceController::class, 'viewAjax'])->name('invoices.view.ajax');

// Invoice Comments
Route::post('invoice-comments', [PaymentInvoiceCommentController::class, 'store'])->name('invoice.comments.store');
Route::get('invoices/{invoice}/comments', [PaymentInvoiceCommentController::class, 'getComments'])->name('invoice.comments.index');

// Auto-invoice
Route::get('autoinvoice', [AutoInvoiceController::class, 'index'])->name('auto.invoice.index');
Route::get('autoinvoice/current', [AutoInvoiceController::class, 'generateCurrent'])->name('auto.invoice.current');
Route::get('autoinvoice/due', [AutoInvoiceController::class, 'generateDue'])->name('auto.invoice.due');

// Transactions
Route::get('transactions/ajax-data', [PaymentTransactionController::class, 'getData'])->name('transactions.ajax-data');
Route::get('transactions/export-data', [PaymentTransactionController::class, 'getExportData'])->name('transactions.export-data');
Route::get('transactions/{id}/download-payslip', [PdfController::class, 'downloadPaySlip'])->name('transactions.download');
Route::post('transactions/{id}/approve', [PaymentTransactionController::class, 'approve'])->name('transactions.approve');

// Costs CRUD
Route::prefix('costs')
    ->name('costs.')
    ->group(function () {
        Route::get('types', [CostController::class, 'types'])->name('types');
        Route::get('check-today', [CostController::class, 'checkToday'])->name('check-today');
        Route::post('/', [CostController::class, 'store'])->name('store');
        Route::get('{id}', [CostController::class, 'show'])->name('show');
        Route::put('{id}', [CostController::class, 'update'])->name('update');
        Route::delete('{id}', [CostController::class, 'destroy'])->name('destroy');
        Route::post('{id}/entries', [CostController::class, 'addEntry'])->name('add-entry');
        Route::delete('entries/{id}', [CostController::class, 'deleteEntry'])->name('delete-entry');
    });

// Settlements
Route::prefix('settlements')
    ->name('settlements.')
    ->group(function () {
        Route::get('/', [SettlementController::class, 'index'])->name('index');
        Route::get('/logs', [SettlementController::class, 'logs'])->name('logs');
        Route::post('/', [SettlementController::class, 'store'])->name('store');
        Route::post('/adjustment', [SettlementController::class, 'adjustment'])->name('adjustment');
        Route::get('/{user}', [SettlementController::class, 'show'])->name('show');
    });

// Resource controllers
Route::resources([
    'invoices'     => PaymentInvoiceController::class,
    'transactions' => PaymentTransactionController::class,
]);