{{--
    Port of tailadmin/resources/views/components/form/form-elements/select-inputs.blade.php:
    `appearance-none` hides the browser's native dropdown arrow so every
    browser/OS shows the same flat custom chevron instead, and the wrapping
    x-data dims the text to gray until an option is actually picked, same as
    the tailadmin demo. Pass any native <select> attribute (wire:model
    included — Livewire's own JS binds to it like any other input) plus a
    slot of <option> tags; `placeholder` renders as the first, empty option.

    The <select> itself is always `w-full` — to size it (a fixed width for
    an inline filter vs. filling a form field), set `wrapper-class` on the
    outer element instead of `class`, since only the wrapper's width is
    visible when the select is absolutely positioned under the chevron.
--}}
@props(['placeholder' => null, 'wrapperClass' => 'w-full'])

<div x-data="{ isOptionSelected: false }" x-init="isOptionSelected = $refs.select.value !== ''" class="relative z-20 bg-transparent {{ $wrapperClass }}">
    <select
        x-ref="select"
        {{ $attributes->merge(['class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90']) }}
        :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
        @change="isOptionSelected = true">
        @if ($placeholder)
            <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">{{ $placeholder }}</option>
        @endif

        {{ $slot }}
    </select>

    <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
        <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
</div>
