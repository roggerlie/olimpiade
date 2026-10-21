<?php

namespace App\Listeners;

use App\Support\LogAktivitas;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

/**
 * Writes every login, logout, failed login and lockout — both guards
 * (`web` admin accounts, `peserta`) — to the `auth` activity log. Events
 * are auto-discovered from this class's handle* methods.
 *
 * Credentials are never stored: only the login id that was typed in
 * (`username` for admin, `noreg` for peserta), never the password.
 */
class CatatAktivitasLogin
{
    public function handleLogin(Login $event): void
    {
        LogAktivitas::catat(LogAktivitas::AUTH, 'login', 'Login berhasil', $event->user, properti: [
            'guard' => $event->guard,
            'metode' => request()->attributes->get('metode_login', 'password'),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        LogAktivitas::catat(LogAktivitas::AUTH, 'logout', 'Logout', $event->user, properti: [
            'guard' => $event->guard,
        ]);
    }

    /**
     * Linked to the account when the login id matched one (someone tried
     * this account), anonymous otherwise.
     */
    public function handleFailed(Failed $event): void
    {
        $alasan = match (true) {
            $event->user === null => 'akun_tidak_ditemukan',
            // Right password, but attemptWhen()'s callback refused it — on the
            // admin form that's an account without an admin-tier role.
            Auth::guard($event->guard)->getProvider()->validateCredentials($event->user, $event->credentials) => 'tidak_punya_akses',
            default => 'password_salah',
        };

        LogAktivitas::catat(LogAktivitas::AUTH, 'login_gagal', 'Login gagal', $event->user, properti: [
            'guard' => $event->guard,
            'metode' => 'password',
            'username' => (string) ($event->credentials['noreg'] ?? $event->credentials['username'] ?? ''),
            'alasan' => $alasan,
        ]);
    }

    /**
     * Fired by App\Http\Requests\Auth\LoginRequest::ensureIsNotRateLimited().
     */
    public function handleLockout(Lockout $event): void
    {
        LogAktivitas::catat(LogAktivitas::AUTH, 'login_diblokir', 'Login diblokir sementara (terlalu banyak percobaan)', properti: [
            'guard' => $event->request->is('admin', 'admin/*') ? 'web' : 'peserta',
            'username' => (string) $event->request->input('username'),
        ]);
    }
}
