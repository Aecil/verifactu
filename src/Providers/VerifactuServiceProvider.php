<?php

namespace Aecil\Verifactu\Providers;

use Aecil\Verifactu\SoapClientVerifactu;
use Illuminate\Support\ServiceProvider;

class VerifactuServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/verifactu.php',
            'verifactu'
        );

        $this->app->singleton(SoapClientVerifactu::class, function ($app) {
            return new SoapClientVerifactu(
                config('verifactu')
            );
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../Config/verifactu.php' => config_path('verifactu.php'),
        ], 'verifactu-config');
    }
}
