<?php

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

Route::group(['middleware' => 'auth'], function () {

    //Customers
    Route::resource('customers', 'CustomersController');
    
    // Customer Billing (for wholesale customers)
    Route::get('customers/billing', 'CustomerBillingController@index')->name('customers.billing.index');
    Route::get('customers/{customer}/billing', 'CustomerBillingController@show')->name('customers.billing.show');
    Route::get('customers/{customer}/billing/create', 'CustomerBillingController@create')->name('customers.billing.create');
    Route::post('customers/{customer}/billing', 'CustomerBillingController@store')->name('customers.billing.store');
    Route::get('customers/{customer}/billing/{billing}/edit', 'CustomerBillingController@edit')->name('customers.billing.edit');
    Route::put('customers/{customer}/billing/{billing}', 'CustomerBillingController@update')->name('customers.billing.update');
    Route::post('customers/{customer}/billing/{billing}/payment', 'CustomerBillingController@addPayment')->name('customers.billing.payment');
    Route::delete('customers/{customer}/billing/{billing}', 'CustomerBillingController@destroy')->name('customers.billing.destroy');
    
    //Suppliers
    Route::resource('suppliers', 'SuppliersController');
    
    // Supplier Billing
    Route::get('suppliers/billing', 'SupplierBillingController@index')->name('suppliers.billing.index');
    Route::get('suppliers/{supplier}/billing', 'SupplierBillingController@show')->name('suppliers.billing.show');
    Route::get('suppliers/{supplier}/billing/create', 'SupplierBillingController@create')->name('suppliers.billing.create');
    Route::post('suppliers/{supplier}/billing', 'SupplierBillingController@store')->name('suppliers.billing.store');
    Route::get('suppliers/{supplier}/billing/{billing}/edit', 'SupplierBillingController@edit')->name('suppliers.billing.edit');
    Route::put('suppliers/{supplier}/billing/{billing}', 'SupplierBillingController@update')->name('suppliers.billing.update');
    Route::post('suppliers/{supplier}/billing/{billing}/payment', 'SupplierBillingController@addPayment')->name('suppliers.billing.payment');
    Route::delete('suppliers/{supplier}/billing/{billing}', 'SupplierBillingController@destroy')->name('suppliers.billing.destroy');

});
