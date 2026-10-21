<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Jenjang;
use App\Models\Pelajaran;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the student login form. The branding panel lists every mapel as a
     * "cabang lomba", each open to every jenjang from master data.
     */
    public function createPeserta(): View
    {
        return view('auth.login-peserta', [
            'cabangLomba' => Pelajaran::query()->orderBy('id')->pluck('nama'),
            'jenjang' => Jenjang::query()->orderBy('kode')->pluck('nama'),
        ]);
    }

    /**
     * Show the admin login form.
     */
    public function createAdmin(): View
    {
        return view('auth.login-admin');
    }

    /**
     * Authenticate a student against the `peserta` guard — its own table,
     * entirely separate from admin accounts (see App\Models\Peserta) — and
     * redirect to the CBT dashboard. The `username` field is kept as the
     * HTTP input name (so LoginRequest/the form don't need to change) but
     * it's matched against `peserta.noreg`, a student's actual login id.
     */
    public function storePeserta(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'noreg' => $request->string('username'),
            'password' => $request->string('password'),
        ];

        $request->ensureIsNotRateLimited();

        if (! Auth::guard('peserta')->attempt($credentials, $request->boolean('remember'))) {
            $request->catatGagal();

            throw ValidationException::withMessages([
                'username' => 'No. Registrasi atau password salah.',
            ]);
        }

        $request->bersihkanPercobaan();
        $request->session()->regenerate();

        return redirect()->intended(route('cbt.dashboard'));
    }

    /**
     * Authenticate an admin (any of the administrator/admin/operator tiers)
     * against the `web` guard and redirect to the admin dashboard.
     */
    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('username', 'password');
        $guard = Auth::guard('web');

        $request->ensureIsNotRateLimited();

        // attemptWhen(), not attempt() + a role check afterwards: an account
        // without an admin-tier role is never logged in at all, so the log
        // records one failed login (see App\Listeners\CatatAktivitasLogin)
        // instead of a successful login immediately followed by a logout.
        $berhasil = $guard->attemptWhen(
            $credentials,
            fn (User $user) => $user->hasAnyRole(['administrator', 'admin', 'operator']),
            $request->boolean('remember'),
        );

        if (! $berhasil) {
            $request->catatGagal();

            $user = $guard->getLastAttempted();
            $tanpaAkses = $user && $guard->getProvider()->validateCredentials($user, $credentials);

            throw ValidationException::withMessages([
                'username' => $tanpaAkses ? 'Akun ini tidak memiliki akses ke halaman tersebut.' : 'Username atau password salah.',
            ]);
        }

        $request->bersihkanPercobaan();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Log the current user out — whichever of the two guards is actually
     * holding a session (peserta and admin are fully separate accounts now,
     * see config/auth.php).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $isAdmin = Auth::guard('web')->check();
        $guard = $isAdmin ? 'web' : 'peserta';

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($isAdmin ? route('admin.login') : route('login'));
    }
}
