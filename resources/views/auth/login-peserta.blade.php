<x-layouts.guest title="Login Peserta">
    <div class="flex min-h-screen w-full items-center justify-center bg-gray-50 p-6 dark:bg-gray-900">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">CBT Olimpiade</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masuk dengan username &amp; password peserta.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
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

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Password
                        </label>
                        <input type="password" id="password" name="password" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>

                    <x-ui.button type="submit" class="w-full">Masuk</x-ui.button>
                </form>
            </div>

            <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
                Panitia? <a href="{{ route('admin.login') }}" class="text-brand-500 hover:text-brand-600">Masuk di sini</a>
            </p>
        </div>
    </div>
</x-layouts.guest>
