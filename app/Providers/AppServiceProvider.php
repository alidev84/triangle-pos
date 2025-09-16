<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Model::preventLazyLoading(!app()->isProduction());
        
        // Register the format_currency helper
        if (!function_exists('format_currency')) {
            function format_currency($value, $currency = null, $decimal = null, $thousand = null) {
                $currency = $currency ?: config('settings.currency_symbol', '$');
                $decimal = $decimal ?: config('settings.decimal_separator', '.');
                $thousand = $thousand ?: config('settings.thousand_separator', ',');
                $decimals = config('settings.decimal_places', 2);
                
                return $currency . ' ' . number_format((float)$value, $decimals, $decimal, $thousand);
            }
        }
        
        // Register Livewire components
        Livewire::component('supplier-payment-form', \App\Livewire\SupplierPaymentForm::class);
    }
}
