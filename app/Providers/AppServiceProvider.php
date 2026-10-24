<?php

namespace App\Providers;

use App\Services\Ambassadors\Content;
use App\Services\Geography\BigDataCloudGeocoder;
use App\Services\Geography\Contracts\ReverseGeocoder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        View::composer(['tmgamb.*', 'components.layouts.tmgamb'], function ($view): void {
            $ambassador = Auth::guard('ambassador')->user();
            $content = app(Content::class);

            $view->with([
                'currentAmbassador' => $ambassador,
                'termsContent' => $content->terms(),
                'noticeContent' => $content->notice(),
                'communityLinks' => $content->community($ambassador),
            ]);
        });
    }
}
