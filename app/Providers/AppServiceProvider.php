<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\TelegramSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

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
        Gate::define('use-translation-manager', function (?User $user) {
            // Your authorization logic
            return true;
            // return $user !== null && ($user->hasRole('admin') || $user->hasRole('Super Admin'));
        });

        if (Schema::hasTable('telegram_settings')) {
            $setting = TelegramSetting::first();
            if ($setting) {
                Config::set('telegram.bots.mybot.token', $setting->webhook_token);
            }
        }
    }
}
