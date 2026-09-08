<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
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
        // Super-admin bypass: `administrator` passes every `can()` /
        // `@can` / `authorize()` check regardless of which permissions are
        // actually assigned to the role, so the permission list in
        // PermissionSeeder never needs to be kept in sync with it.
        Gate::before(fn ($user, string $ability) => $user->hasRole('administrator') ? true : null);

        // Force Indonesian for date/duration formatting (Carbon's translatedFormat()/
        // diffForHumans(), used across Ujian, Peserta-Ujian, and CBT views), matching
        // the rest of this app's UI text. Set explicitly here rather than relying on
        // APP_LOCALE alone: some local dev setups have an APP_LOCALE=en value baked
        // into the PHP process's own OS environment (visible via getenv(), ahead of
        // whatever .env says) that config()/env() can't override — see the "id" note
        // in .env. This line is the actual source of truth regardless of that.
        Carbon::setLocale('id');
    }
}
