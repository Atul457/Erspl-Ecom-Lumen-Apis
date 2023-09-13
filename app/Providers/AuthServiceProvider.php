<?php

namespace App\Providers;

use App\Models\Registration;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
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
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish tbl_registration to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a Registration instance or null. You're free to obtain
        // the Registration instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            if ($request->input('api_token')) {
                return Registration::where('api_token', $request->input('api_token'))->first();
            }
        });
    }
}
