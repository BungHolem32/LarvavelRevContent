<?php
/**
 * Created by PhpStorm.
 * User: ilan
 * Date: 29/07/16
 * Time: 01:48
 */

namespace App\Providers;


use Illuminate\Support\ServiceProvider;

class RevContentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('\\app\\Http\\Models\\Interfaces\\RevContentInterface', function ($app) {
            return $app->make('app\\Http\\Models\\Services\\RevContentService');
        });

    }

}