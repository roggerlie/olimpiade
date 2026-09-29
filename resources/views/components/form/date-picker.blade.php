{{--
    Ported from tailadmin/resources/views/components/form/date-picker.blade.php,
    extended with `enableTime` (needed for sesi_mulai/sesi_selesai — this
    app has no plain-date-only picker yet) and Livewire wire:model support:
    tailadmin's own version only fires an Alpine `date-change` event, but
    flatpickr sets the input's value programmatically, which doesn't trigger
    the native 'input' event wire:model listens for — so onChange below
    dispatches one manually.

    IMPORTANT — always pass an explicit `id` when using this inside a
    Livewire component. The `uniqid()` default is re-evaluated on every
    render, so on a Livewire re-render (e.g. after calling create()/edit())
    the id changes, the wrapper's identity looks different to Livewire's
    morph, and the flatpickr instance attached to the old node gets torn
    down without a new one replacing it — the field silently stops opening
    its calendar. A stable id avoids this entirely.

    ALSO IMPORTANT — do not pass `static: true` to flatpickr here. That mode
    makes flatpickr wrap the input in its own `<div class="flatpickr-wrapper">`
    (extra DOM the server-rendered HTML doesn't have), and the very next
    Livewire morph — any wire-triggered update while the field is on screen,
    edit() included — sees that mismatch and replaces the input rather than
    patching it in place. `x-init` only runs once per component mount, so it
    never reinitializes on the replacement node: the calendar silently stops
    opening (confirmed live — the flatpickr instance is left pointing at the
    old, now-detached input). Leaving flatpickr in its default (non-static)
    mode both avoids the wrapper entirely and appends the calendar to
    `document.body`, which happens to also be how it escapes this modal's
    `overflow-y-auto` clipping and lands above it (flatpickr's default
    z-index already matches x-ui.modal's).

    `altFormat` — optional human-friendly display format (e.g. 'd M Y, H:i'):
    flatpickr then shows a second, visible input in that format and turns the
    original into a hidden one still holding `dateFormat` for wire:model.
    That extra input is exactly the kind of DOM Livewire's morph would rip
    out, so the input area is wire:ignore'd, and server-side changes to the
    bound property (edit()/reset()) are pushed into flatpickr via $wire.$watch
    instead of relying on morph.
--}}
@props([
    'id' => 'datepicker-'.uniqid(),
    'mode' => 'single',
    'enableTime' => false,
    'defaultDate' => null,
    'label' => null,
    'placeholder' => 'Pilih tanggal',
    'dateFormat' => 'Y-m-d',
    'altFormat' => null,
])

@php
    $model = $attributes->wire('model')->value();
    $inputClass = 'h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent py-2.5 pl-4 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
@endphp

<div x-data="{
        flatpickrInstance: null,
        init() {
            this.$nextTick(() => {
                this.flatpickrInstance = flatpickr(this.$refs.dateInput, {
                    mode: @js($mode),
                    enableTime: @js((bool) $enableTime),
                    time_24hr: true,
                    appendTo: document.body,
                    monthSelectorType: 'static',
                    dateFormat: @js($dateFormat),
                    altInput: @js((bool) $altFormat),
                    altFormat: @js($altFormat ?? ''),
                    altInputClass: @js($inputClass),
                    defaultDate: @js($defaultDate),
                    onChange: () => {
                        this.$refs.dateInput.dispatchEvent(new Event('input', { bubbles: true }));
                    },
                    // Livewire morphs the raw input value directly when this
                    // field is populated from the server (e.g. an edit()
                    // action) — flatpickr doesn't see that on its own, so
                    // re-sync its internal selection from the input's
                    // current text whenever the calendar opens.
                    onOpen: (selectedDates, dateStr, instance) => {
                        if (instance.input.value) {
                            instance.setDate(instance.input.value, false);
                        }
                    },
                });

                @if ($model && $altFormat)
                    this.$wire.$watch(@js($model), (value) => {
                        value ? this.flatpickrInstance.setDate(value, false) : this.flatpickrInstance.clear(false);
                    });
                @endif
            });
        },
        destroy() {
            this.flatpickrInstance?.destroy();
        },
    }"
    x-init="init()" x-destroy="destroy()">
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ $label }}
        </label>
    @endif

    <div class="custom-datepicker relative" @if ($altFormat) wire:ignore @endif>
        <input x-ref="dateInput" type="text" id="{{ $id }}" placeholder="{{ $placeholder }}" autocomplete="off"
            {{ $attributes->merge(['class' => $inputClass]) }} />

        <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z" fill="currentColor" />
            </svg>
        </span>
    </div>
</div>
