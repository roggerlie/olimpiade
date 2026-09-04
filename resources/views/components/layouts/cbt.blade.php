@props(['title' => 'CBT'])

<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | CBT Olimpiade</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-scripts')
</head>

{{--
    Deliberately minimal chrome — no admin sidebar, no distractions — so a
    student's screen is dominated by the question and the timer. See
    resources/views/cbt/kerjakan.blade.php for the exam-taking page itself.
--}}
<body class="dark:bg-gray-900 dark:text-white/90" x-data>
    <div class="min-h-screen">
        <header class="sticky top-0 z-50 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <span class="text-sm font-semibold text-gray-800 dark:text-white/90">CBT Olimpiade</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-error-500 dark:text-gray-400">
                    Keluar
                </button>
            </form>
        </header>

        <main class="mx-auto max-w-(--breakpoint-xl) p-4 md:p-6">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>

</html>
