<?php

use Illuminate\Support\Facades\Route;
use Modules\PurchasesReturn\Http\Controllers\Api\PurchaseReturnApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Remove auth middleware since you're calling these from web pages
Route::prefix('purchase-returns')->group(function () {
    Route::get('/recent', [PurchaseReturnApiController::class, 'getRecentPurchases']);
    Route::get('/search', [PurchaseReturnApiController::class, 'search']);
    Route::get('/suggestions', [PurchaseReturnApiController::class, 'suggestions']);
    Route::get('/{id}/details', [PurchaseReturnApiController::class, 'getPurchaseDetails']);
});