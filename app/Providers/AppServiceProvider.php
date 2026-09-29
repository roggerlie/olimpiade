<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Console\ServeCommand;
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
        // PermissionSeeder never needs to be kept in sync with it. Gate::before
        // runs for every check regardless of guard, so it also sees Peserta
        // (the `peserta` guard's user, see App\Models\Peserta) — which has no
        // Spatie roles at all, hence the User instanceof guard below.
        Gate::before(fn ($user, string $ability) => $user instanceof User && $user->hasRole('administrator') ? true : null);

        // Force Indonesian for date/duration formatting (Carbon's translatedFormat()/
        // diffForHumans(), used across Ujian, Peserta-Ujian, and CBT views), matching
        // the rest of this app's UI text. Set explicitly here rather than relying on
        // APP_LOCALE alone: some local dev setups have an APP_LOCALE=en value baked
        // into the PHP process's own OS environment (visible via getenv(), ahead of
        // whatever .env says) that config()/env() can't override — see the "id" note
        // in .env. This line is the actual source of truth regardless of that.
        Carbon::setLocale('id');

        // `php artisan serve` only forwards whitelisted env vars to the PHP
        // dev-server process. On Windows that drops TEMP/TMP, so PHP falls back
        // to C:\Windows\Temp (not writable for a normal user) and every file
        // upload (e.g. Bank Soal Excel import) fails with "unable to create a
        // temporary file in Unknown on line 0" before reaching our code.
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables = array_values(array_unique([
                ...ServeCommand::$passthroughVariables, 'TEMP', 'TMP', 'TMPDIR',
            ]));
        }
    }
}
