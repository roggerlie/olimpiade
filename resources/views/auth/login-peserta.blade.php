{{-- Split-screen layout ported from tailadmin/resources/views/pages/auth/signin.blade.php --}}
<x-layouts.guest title="Login Peserta">
    <div class="relative flex min-h-screen w-full flex-col bg-white lg:flex-row dark:bg-gray-900">
        {{-- Form --}}
        <div class="flex w-full flex-1 flex-col lg:w-1/2">
            <div class="mx-auto w-full max-w-md px-6 pt-10">
                <a href="{{ url('/') }}"
                    class="inline-flex items-center gap-2 text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Kembali ke Beranda
                </a>
            </div>

            <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-6 py-10">
                <div class="mb-6">
                    <h1 class="mb-2 text-title-sm font-semibold text-gray-800 sm:text-title-md dark:text-white/90">
                        Login Peserta
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Masuk dengan username &amp; password peserta untuk mengerjakan ujian.
                    </p>
                </div>

                @if ($errors->any())
                    <x-ui.alert variant="error" title="Login gagal" class="mb-5">
                        {{ $errors->first() }}
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

                <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
                    Panitia? <a href="{{ route('admin.login') }}" class="text-brand-500 hover:text-brand-600">Masuk di sini</a>
                </p>
            </div>
        </div>

        <x-auth.brand-panel tagline="Portal Computer Based Test untuk peserta olimpiade. Kerjakan ujianmu dengan tenang dan percaya diri." />
    </div>
</x-layouts.guest>
