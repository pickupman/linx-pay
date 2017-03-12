<?php
namespace Pickupmna\Providers;

use Illuminate\Support\ServiceProvider;

class LinxPayServiceProvider extends ServiceProvider {

     /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('LinxPay',function(){
            return new \Pickupman\LinxPay;
        });
    }

}