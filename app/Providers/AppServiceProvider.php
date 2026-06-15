<?php

namespace App\Providers;

use App\Channels\FirebaseChannel;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;


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
        //
        Validator::extend('iso_date', 'Penance316\Validators\IsoDateValidator@validateIsoDate');

        $this->app->singleton(FirebaseChannel::class, function ($app) {
            return new FirebaseChannel($app->make(FirebaseService::class));
        });
    }
}
