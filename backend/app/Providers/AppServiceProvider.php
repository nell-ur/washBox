<?php

namespace App\Providers;

use App\Models\Laundry;
use App\Models\Branch;
use App\Models\Promotion;
use Laravel\Sanctum\Sanctum;
use App\Observers\LaundryObserver;
use App\Observers\PromotionObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Models\PickupRequest; // ✅ Added
use Laravel\Sanctum\PersonalAccessToken;
use App\Observers\PickupRequestObserver; // ✅ Added

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
        Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);

        // ✅ Register the Observer for Pickup Requests
        PickupRequest::observe(PickupRequestObserver::class);
        Promotion::observe(PromotionObserver::class);
        Laundry::observe(LaundryObserver::class);

        // Check if the table exists before sharing with views
        if (!app()->runningInConsole() && Schema::hasTable('branches')) {
            view()->share('branches', Branch::all());
            Paginator::useBootstrapFive();
        }
    }
}
