<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
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
        Http::macro('remote', function () {
            return Http::withoutVerifying()->withToken(config('remote.api_token'))->baseUrl(config('remote.url'));
        });

        Http::macro('panel', function () {
            return Http::withoutVerifying()->withToken(config('panel.admin_token'))->withHeaders([
                'Accept' => 'Application/vnd.pterodactyl.v1+json',
            ])->baseUrl(config('panel.url') . '/api/client');
        });
    }
}
