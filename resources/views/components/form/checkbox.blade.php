{{--
    Custom-styled checkbox, ported from tailadmin's auth signin page
    (native input visually hidden, a styled box driven by Alpine instead).
--}}
@props(['name', 'checked' => false])

<div x-data="{ checked: {{ $checked ? 'true' : 'false' }} }">
    <label for="{{ $name }}" class="flex cursor-pointer select-none items-center text-sm font-normal text-gray-700 dark:text-gray-400">
        <span class="relative mr-3">
            <input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1" x-model="checked" class="sr-only" />
            <span :class="checked ? 'border-brand-500 bg-brand-500' : 'border-gray-300 bg-transparent dark:border-gray-700'"
                class="flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                <svg :class="checked ? '' : 'opacity-0'" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </span>
        {{ $slot }}
    </label>
</div>
