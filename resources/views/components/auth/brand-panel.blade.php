{{--
    Right-side branding panel for the split-screen auth layout, styled after
    tailadmin/resources/views/pages/auth/signin.blade.php's grid markup —
    text wordmark instead of an image logo since this app has no logo asset.
--}}
@props(['tagline' => ''])

<div class="relative hidden w-full items-center bg-brand-950 lg:flex lg:w-1/2 dark:bg-white/5">
    <div class="relative z-1 flex w-full items-center justify-center overflow-hidden">
        <x-common.common-grid-shape />

        <div class="flex max-w-xs flex-col items-center text-center">
            <a href="{{ url('/') }}" class="mb-4 text-2xl font-bold text-white">
                CBT Olimpiade
            </a>
            <p class="text-gray-400 dark:text-white/60">
                {{ $tagline }}
            </p>
        </div>
    </div>
</div>
