<?php

namespace Hachchadi\CmiPayment;

use Illuminate\Support\ServiceProvider;

class CmiPaymentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/cmi.php' => config_path('cmi.php'),
        ], 'config');
        
        // $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/cmi.php', 'cmi'
        );

        $this->app->singleton(CmiClient::class, function () {
            return new CmiClient();
        });
    }
}
