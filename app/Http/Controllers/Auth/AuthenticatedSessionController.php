<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the student login form.
     */
    public function createPeserta(): View
    {
        return view('auth.login-peserta');
    }

    /**
     * Show the admin login form.
     */
    public function createAdmin(): View
    {
        return view('auth.login-admin');
    }

    /**
     * Authenticate a student and redirect to the CBT dashboard.
     */
    public function storePeserta(LoginRequest $request): RedirectResponse
    {
        $this->authenticate($request, ['peserta']);

        return redirect()->intended(route('cbt.dashboard'));
    }

    /**
     * Authenticate an admin (any of the administrator/admin/operator tiers)
     * and redirect to the admin dashboard.
     */
    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $this->authenticate($request, ['administrator', 'admin', 'operator']);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Attempt authentication and verify the user holds one of the expected
     * roles, so a student can't sign in through the admin form or vice versa.
     *
     * @param  list<string>  $roles
     */
    private function authenticate(LoginRequest $request, array $roles): void
    {
        $credentials = $request->only('username', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        if (! Auth::user()->hasAnyRole($roles)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Akun ini tidak memiliki akses ke halaman tersebut.',
            ]);
        }

        $request->session()->regenerate();
    }

    /**
     * Log the current user out, regardless of role.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $wasAdmin = Auth::user()?->hasAnyRole(['administrator', 'admin', 'operator']);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($wasAdmin ? route('admin.login') : route('login'));
    }
}
