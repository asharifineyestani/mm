<?php

namespace App\Providers;

use App\Sh4Classes\ShaHashing;
use Illuminate\Support\ServiceProvider;

class ShaHashServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('hash', function() { return new ShaHashing($this->app); });

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
