<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Login dengan Google" for /admin/login only. Deliberately link-only: it
 * signs in an admin-tier account whose email already matches the Google
 * account, and never creates one. Provisioning that email onto an account
 * happens separately, by another admin, on the Kelola Pengguna page (see
 * resources/views/components/admin/users/⚡manager) — an account with no
 * email on file simply can't be reached through this flow.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Login dengan Google gagal atau dibatalkan.']);
        }

        $user = User::whereRaw('lower(email) = ?', [Str::lower($googleUser->getEmail() ?? '')])->first();

        if (! $user || ! $user->hasAnyRole(['administrator', 'admin', 'operator'])) {
            LogAktivitas::catat(LogAktivitas::AUTH, 'login_gagal', 'Login gagal', $user, properti: [
                'guard' => 'web',
                'metode' => 'google',
                'username' => $googleUser->getEmail(),
                'alasan' => $user ? 'tidak_punya_akses' : 'akun_tidak_ditemukan',
            ]);

            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Akun Google ini tidak terhubung ke akun admin manapun.']);
        }

        // Read by App\Listeners\CatatAktivitasLogin when the Login event fires.
        request()->attributes->set('metode_login', 'google');
        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
