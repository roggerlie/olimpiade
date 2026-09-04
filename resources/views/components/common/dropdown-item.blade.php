{{--
    One row-action menu item, for use inside x-common.table-actions.
    `as="a"` renders a plain navigation link instead of a wire:click button.
--}}
@props(['danger' => false, 'as' => 'button'])

@php
    $class = 'flex w-full rounded-lg px-3 py-2 text-left font-medium text-theme-xs '
        . ($danger
            ? 'text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10'
            : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300');
@endphp

@if ($as === 'a')
    <a {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
