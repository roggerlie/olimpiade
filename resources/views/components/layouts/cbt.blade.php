@props(['title' => 'CBT'])

<!DOCTYPE html>
{{--
    Always dark — the PBSF Gold / Biru / Hijau Hujan stage, same as the
    peserta login. `dark` is set statically and partials.theme-scripts is
    deliberately NOT included: its Alpine store would strip the class again
    for anyone whose saved preference is light. Shared tailadmin components
    (dropdown, alerts) pick up their dark: variants from it.
--}}
<html lang="id" class="dark h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | CBT Olimpiade</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

{{--
    Deliberately minimal chrome — no admin sidebar, no distractions — so a
    student's screen is dominated by the question and the timer. See
    resources/views/cbt/kerjakan.blade.php for the exam-taking page itself.
--}}
<body class="cbt-theme bg-pbsf-ink font-outfit text-white" x-data>
    <x-auth.pbsf-backdrop calm />

    <div class="relative min-h-screen">
        <header class="sticky top-0 z-50 border-b border-white/10 bg-pbsf-ink/70 backdrop-blur-xl">
            <div class="absolute inset-x-0 bottom-0 h-px bg-linear-to-r from-pbsf-gold/60 via-pbsf-blue/60 to-pbsf-rain/60"></div>

            <div class="mx-auto flex max-w-(--breakpoint-xl) items-center justify-between gap-4 px-4 py-2.5 md:px-6">
                <a href="{{ route('cbt.dashboard') }}" class="flex items-center gap-3">
                    <img src="/images/pbsf/logo-pbsf-3d.webp" alt="Panca Budi School Fest Vol. 04" width="816" height="382"
                        class="h-10 w-auto drop-shadow-[0_6px_14px_rgba(0,0,0,.5)]" />
                    <span class="hidden border-l border-white/15 pl-3 text-xs leading-tight font-semibold tracking-[.12em] text-[#8e9acb] uppercase sm:block">
                        Portal CBT<br><span class="text-pbsf-gold-light">Peserta</span>
                    </span>
                </a>

                {{-- User menu --}}
                <x-common.dropdown-menu>
                    <x-slot name="button">
                        <div class="flex items-center gap-2.5 rounded-full border border-white/10 bg-white/[.05] py-1 pr-3 pl-1 text-sm font-medium text-[#dfe5f5] transition hover:bg-white/10">
                            <x-cbt.avatar :name="auth('peserta')->user()->nama" class="size-8 text-xs" />
                            <span class="hidden max-w-48 truncate sm:block">{{ auth('peserta')->user()->nama }}</span>
                            <svg class="size-4 text-[#8e9acb]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.06l3.71-3.83a.75.75 0 1 1 1.08 1.04l-4.25 4.39a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </x-slot>

                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-theme-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Zm12.22 3.47a.75.75 0 0 1 1.06 0l1.75 1.75a.75.75 0 0 1 0 1.06l-1.75 1.75a.75.75 0 1 1-1.06-1.06l.47-.47H8.75a.75.75 0 0 1 0-1.5h6.94l-.47-.47a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </x-slot>
                </x-common.dropdown-menu>
            </div>
        </header>

        <main class="mx-auto max-w-(--breakpoint-xl) p-4 md:p-6">
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>

</html>
