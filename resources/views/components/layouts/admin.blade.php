@props(['title' => 'Dashboard', 'pageTitle' => null])

<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | Admin Olimpiade</title>

    {{--
        resources/js/admin.js on purpose, not app.js — Livewire bundles its
        own Alpine instance via @livewireScripts below, and app.js imports
        Alpine too, so loading it here would start a second, conflicting
        instance. admin.js only exposes window.flatpickr for the date-picker
        component — see resources/js/admin.js.
    --}}
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @include('partials.theme-scripts')
    @livewireStyles

    {{-- Per-page extra <head> assets (e.g. the Soal manager pushing
        resources/js/soal-editor.js) that shouldn't load on every admin
        page the way admin.js does. --}}
    @stack('head')
</head>

<body
    x-data="{ loaded: true }"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1280) {
                $store.sidebar.setMobileOpen(false);
                $store.sidebar.isExpanded = false;
            } else {
                $store.sidebar.isMobileOpen = false;
                $store.sidebar.isExpanded = true;
            }
        });"
    class="dark:bg-gray-900"
>
    <x-common.preloader />

    <div class="min-h-screen xl:flex">
        {{-- Mobile overlay --}}
        <div x-show="$store.sidebar.isMobileOpen" @click="$store.sidebar.setMobileOpen(false)"
            class="fixed z-50 h-screen w-full bg-gray-900/50" x-cloak></div>

        @include('layouts.partials.admin-sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
            }">
            @include('layouts.partials.admin-header')

            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                @if ($pageTitle)
                    <x-common.page-breadcrumb :page-title="$pageTitle" />
                @endif

                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>
