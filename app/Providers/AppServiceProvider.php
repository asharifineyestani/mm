<?php

namespace App\Providers;

use App\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use \Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $roles = Role::all();

       View::share('roles', $roles);

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
