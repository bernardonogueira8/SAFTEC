<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\StabilityConsultation;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use App\Observers\StabilityConsultationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Coolify
        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
        }

        Gate::policy(Activity::class, ActivityPolicy::class);

        StabilityConsultation::observe(StabilityConsultationObserver::class);
    }
}
