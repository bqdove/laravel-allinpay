<?php
namespace Weijian\AllinPay;

use Illuminate\Support\ServiceProvider;
class AllinPayServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/Config/allinpay.php' => config_path('allinpay.php'),
        ]);
    }
    public function register()
    {
        $this->app->singleton('Allinpay', function ($app) {
            return new AllinpayFactory();
        });
    }
}