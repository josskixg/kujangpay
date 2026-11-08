<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentGatewayController;

Route::prefix('v1/kujangpay')->group(function () {
    Route::post('/invoices', [PaymentGatewayController::class, 'createInvoice']);
    Route::post('/webhook/mutasi', [PaymentGatewayController::class, 'handleBankMutation']);
});
