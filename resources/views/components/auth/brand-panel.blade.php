{{--
    Right-side branding panel for the split-screen auth layout, styled after
    tailadmin/resources/views/pages/auth/signin.blade.php's grid markup.
--}}
@props(['tagline' => ''])

<div class="relative hidden w-full items-center bg-brand-950 lg:flex lg:w-1/2 dark:bg-white/5">
    <div class="relative z-1 flex w-full items-center justify-center overflow-hidden">
        <x-common.common-grid-shape />

        <div class="flex max-w-xs flex-col items-center text-center">
            <a href="{{ url('/') }}" class="mb-4 flex flex-col items-center gap-3">
                <img src="/images/logo/logo-icon.svg" alt="CBT Olimpiade" width="48" height="48" />
                <span class="text-2xl font-bold text-white">CBT Olimpiade</span>
            </a>
            <p class="text-gray-400 dark:text-white/60">
                {{ $tagline }}
            </p>
        </div>
    </div>
</div>
