<?php

namespace App\Providers;

use App\Models\SeenNotice;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.app', function ($view) {
            $userId = session('api_user.id');

            $view->with('showWelcomeNotice', $userId && SeenNotice::shouldShow($userId, 'welcome'));
            $view->with('showCoffeeNotice', $userId && SeenNotice::shouldShow($userId, 'coffee'));
        });
    }
}
