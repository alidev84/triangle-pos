<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/home');
});

Auth::routes(['register' => false]);

Route::group(['middleware' => 'auth'], function () {
    Route::post('/sale-returns/process', [\Modules\SalesReturn\Http\Controllers\SaleReturnController::class, 'processReturn'])->name('sale-returns.process');
    Route::post('/purchase-returns/process', [\Modules\PurchasesReturn\Http\Controllers\PurchaseReturnController::class, 'processReturn'])->name('purchase-returns.process');
    Route::get('/home', 'HomeController@index')
        ->name('home');

    Route::get('/sales-purchases/chart-data', 'HomeController@salesPurchasesChart')
        ->name('sales-purchases.chart');

    Route::get('/current-month/chart-data', 'HomeController@currentMonthChart')
        ->name('current-month.chart');

    Route::get('/payment-flow/chart-data', 'HomeController@paymentChart')
        ->name('payment-flow.chart');
});

