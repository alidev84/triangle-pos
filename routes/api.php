<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Sales API routes for new return system
Route::get('/sales/search', [\Modules\Sale\Http\Controllers\SaleController::class, 'searchTransactions']);
Route::get('/sales/recent', [\Modules\Sale\Http\Controllers\SaleController::class, 'getRecentSales']);
Route::get('/sales/{id}/products', [\Modules\Sale\Http\Controllers\SaleController::class, 'getSaleProducts']);
Route::get('/sales/suggestions', [\Modules\Sale\Http\Controllers\SaleController::class, 'getSuggestions']);

// Purchases API routes for new return system
Route::get('/purchases/search', [\Modules\Purchase\Http\Controllers\PurchaseController::class, 'searchTransactions']);
Route::get('/purchases/recent', [\Modules\Purchase\Http\Controllers\PurchaseController::class, 'getRecentPurchases']);
Route::get('/purchases/{id}/products', [\Modules\Purchase\Http\Controllers\PurchaseController::class, 'getPurchaseProducts']);
Route::get('/purchases/suggestions', [\Modules\Purchase\Http\Controllers\PurchaseController::class, 'getSuggestions']);

// Supplier Payment API routes
Route::prefix('suppliers/{supplier}')->group(function () {
    Route::get('/payments', [\App\Http\Controllers\SupplierPaymentController::class, 'index']);
    Route::post('/payments', [\App\Http\Controllers\SupplierPaymentController::class, 'store']);
    Route::get('/payments/{payment}', [\App\Http\Controllers\SupplierPaymentController::class, 'show']);
    Route::delete('/payments/{payment}', [\App\Http\Controllers\SupplierPaymentController::class, 'destroy']);
    
    // Get unpaid purchases for a supplier
    Route::get('/unpaid-purchases', function (\Modules\People\Entities\Supplier $supplier) {
        return response()->json($supplier->unpaidPurchases);
    });
});
