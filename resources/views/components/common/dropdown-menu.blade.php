{{--
    Trimmed port of tailadmin/resources/views/components/common/table-dropdown.blade.php
    (merged with dropdown-menu.blade.php's simpler API): same button/content
    slots and panel styling, but positioned with plain Tailwind (absolute +
    align) instead of Popper.js. Admin pages don't load resources/js/app.js
    (Livewire brings its own Alpine — see components/layouts/admin.blade.php),
    so there's no window.createPopper to hook into; plain positioning covers
    every place this app opens a dropdown (header user menu, table row
    actions) without a second JS entry point.

    Usage:
        <x-common.dropdown-menu>
            <x-slot name="button"> ...trigger... </x-slot>
            <x-slot name="content"> ...menu items... </x-slot>
        </x-common.dropdown-menu>
--}}
@props(['align' => 'right', 'width' => 'w-48'])

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <div @click="open = !open" class="cursor-pointer">
        {{ $button }}
    </div>

    <div x-show="open" x-cloak x-transition
        class="absolute z-50 {{ $align === 'right' ? 'right-0' : 'left-0' }} top-full mt-2 {{ $width }} space-y-1 rounded-2xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark"
        role="menu" @click="open = false">
        {{ $content }}
    </div>
</div>
