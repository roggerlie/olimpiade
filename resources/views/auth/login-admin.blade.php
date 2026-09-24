{{-- Centered single-column layout — no split-screen brand panel on this page. --}}
<x-layouts.guest title="Login Admin">
    <div class="relative flex min-h-screen w-full flex-col items-center justify-center bg-gray-50 p-6 dark:bg-gray-900">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-theme-lg dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-6 text-center">
                <img src="{{ asset('images/logo/logo-icon.svg') }}" alt="CBT Olimpiade" class="mx-auto mb-4 h-12 w-12">
                <h1 class="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                    Login Admin
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Masuk untuk mengelola ujian &amp; peserta.
                </p>
            </div>

            @if ($errors->any())
                <x-ui.alert variant="error" title="Login gagal" class="mb-5">
                    {{ $errors->first() }}
                </x-ui.alert>
            @endif

            <a href="{{ route('admin.login.google') }}"
                class="flex h-11 w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.6 10.23c0-.68-.06-1.33-.17-1.96H10v3.71h5.38a4.6 4.6 0 0 1-2 3.02v2.5h3.23c1.89-1.74 2.98-4.3 2.98-7.27Z" fill="#4285F4" />
                    <path d="M10 20c2.7 0 4.96-.9 6.61-2.43l-3.23-2.5c-.9.6-2.05.96-3.38.96-2.6 0-4.8-1.75-5.59-4.11H1.07v2.59A10 10 0 0 0 10 20Z" fill="#34A853" />
                    <path d="M4.41 11.92a5.99 5.99 0 0 1 0-3.84V5.49H1.07a10 10 0 0 0 0 9.02l3.34-2.59Z" fill="#FBBC05" />
                    <path d="M10 3.98c1.47 0 2.79.5 3.83 1.5l2.87-2.87A9.95 9.95 0 0 0 10 0a10 10 0 0 0-8.93 5.49l3.34 2.59C5.2 5.73 7.4 3.98 10 3.98Z" fill="#EA4335" />
                </svg>
                Masuk dengan Google
            </a>

            <div class="my-5 flex items-center gap-3">
                <span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>
                <span class="text-xs text-gray-400">atau</span>
                <span class="h-px flex-1 bg-gray-200 dark:bg-gray-800"></span>
            </div>

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="username" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Username
                    </label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>

                <x-form.password-input />

                <x-form.checkbox name="remember">Ingat saya</x-form.checkbox>

                <x-ui.button type="submit" class="w-full">Masuk</x-ui.button>
            </form>
        </div>
    </div>
</x-layouts.guest>
