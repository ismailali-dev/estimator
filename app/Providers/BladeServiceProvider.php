<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('price', function ($expression) {
            return "<?php echo App\Helpers\Helper::price($expression); ?>";
        });


        Blade::if('admin', function () {
            $conditon = false;

            // check if the user is authenticated
            if (Auth::check()) {
                $condition = Auth::user()->type == "ADMIN";
            }

            return $condition;
        });


        Blade::if('company', function () {
            $conditon = false;

            // check if the user is authenticated
            if (Auth::check()) {
                $condition = Auth::user()->type == "COMPANY";
            }

            return $condition;
        });


        Blade::if('isSubscribed', function () {
            $conditon = false;
            // check if the user is authenticated
            if (Auth::check()) {
                $condition = Auth::user()->type != "ADMIN" && !Auth::user()->isExpired;
            }

            return $condition;
        });


        Blade::if('isNotSubscribed', function () {
            $conditon = false;
            // check if the user is authenticated
            if (Auth::check()) {
                $condition = Auth::user()->type != "ADMIN" && Auth::user()->isExpired;
            }

            return $condition;
        });
    }
}
