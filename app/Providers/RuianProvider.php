<?php

namespace App\Providers;

use App\Ruian\Ruian;
use Illuminate\Support\ServiceProvider;

class RuianProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Ruian::class);
    }
}
