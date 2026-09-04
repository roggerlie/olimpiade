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
    public function createSiswa(): View
    {
        return view('auth.login-siswa');
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
    public function storeSiswa(LoginRequest $request): RedirectResponse
    {
        $this->authenticate($request, 'siswa');

        return redirect()->intended(route('cbt.dashboard'));
    }

    /**
     * Authenticate an admin and redirect to the admin dashboard.
     */
    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $this->authenticate($request, 'admin');

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Attempt authentication and verify the user holds the expected role,
     * so a student can't sign in through the admin form or vice versa.
     */
    private function authenticate(LoginRequest $request, string $role): void
    {
        $credentials = $request->only('username', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        if (! Auth::user()->hasRole($role)) {
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
        $wasAdmin = Auth::user()?->hasRole('admin');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($wasAdmin ? route('admin.login') : route('login'));
    }
}
