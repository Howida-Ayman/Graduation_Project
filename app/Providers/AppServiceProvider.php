<?php

namespace App\Providers;

use App\Models\Proposal;
use App\Observers\ProposalObserver;
use App\Services\ChatService;
use Illuminate\Support\ServiceProvider;
use App\Models\DatabaseNotification;
use App\Observers\NotificationObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // تسجيل ChatService كـ Singleton
        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Proposal::observe(ProposalObserver::class);

         DatabaseNotification::observe(NotificationObserver::class);
    }
}