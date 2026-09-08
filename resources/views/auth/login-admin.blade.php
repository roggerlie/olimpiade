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
