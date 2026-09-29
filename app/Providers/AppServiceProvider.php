<?php

namespace App\Providers;

use App\Services\Geography\BigDataCloudGeocoder;
use App\Services\Geography\Contracts\ReverseGeocoder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReverseGeocoder::class, BigDataCloudGeocoder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
    }
}
