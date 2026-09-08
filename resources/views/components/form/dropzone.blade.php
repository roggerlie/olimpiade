{{--
    Drag-and-drop file picker, ported from
    tailadmin/resources/views/components/form/form-elements/dropzone.blade.php
    — single-file only (tailadmin's demo accepts multiple images; this app
    only ever imports one spreadsheet at a time) and adapted for Livewire's
    WithFileUploads: pass `wire:model` like any other input (it lands on the
    hidden native <input> via $attributes) and pass the currently-selected
    filename in via `fileName` — sourced from a Livewire computed property
    server-side, not client Alpine state, so it correctly clears itself
    whenever the component resets $file (e.g. reopening the modal), which a
    purely client-tracked filename wouldn't.

    `previewUrl` is optional — pass it (e.g. a soal's existing/newly-uploaded
    image URL) to show a thumbnail instead of just the filename, for the
    image-upload slots on the Soal form. Leave it out for non-image uploads
    (Peserta's Excel import) and it behaves exactly as before.
--}}
@props(['accept' => '*', 'fileName' => null, 'previewUrl' => null])

<div x-data="{
        isDragging: false,
        handleDrop(e) {
            this.isDragging = false;
            if (e.dataTransfer.files.length) {
                this.$refs.fileInput.files = e.dataTransfer.files;
                this.$refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
    }">
    <div @drop.prevent="handleDrop($event)" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
        @click="$refs.fileInput.click()"
        :class="isDragging
            ? 'border-brand-500 bg-gray-100 dark:bg-gray-800'
            : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900'"
        class="cursor-pointer rounded-xl border border-dashed p-7 text-center transition-colors hover:border-brand-500">

        <input x-ref="fileInput" type="file" accept="{{ $accept }}" class="hidden" @click.stop {{ $attributes }} />

        @if ($previewUrl)
            <img src="{{ $previewUrl }}" alt="Pratinjau" class="mx-auto mb-3 h-20 w-20 rounded-lg object-cover" />
        @else
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-gray-600 dark:bg-white/5 dark:text-gray-400">
                <svg class="fill-current" width="20" height="20" viewBox="0 0 29 28" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5019 3.91699C14.2852 3.91699 14.0899 4.00891 13.953 4.15589L8.57363 9.53186C8.28065 9.82466 8.2805 10.2995 8.5733 10.5925C8.8661 10.8855 9.34097 10.8857 9.63396 10.5929L13.7519 6.47752V18.667C13.7519 19.0812 14.0877 19.417 14.5019 19.417C14.9161 19.417 15.2519 19.0812 15.2519 18.667V6.48234L19.3653 10.5929C19.6583 10.8857 20.1332 10.8855 20.426 10.5925C20.7188 10.2995 20.7186 9.82463 20.4256 9.53184L15.0838 4.19378C14.9463 4.02488 14.7367 3.91699 14.5019 3.91699ZM5.91626 18.667C5.91626 18.2528 5.58047 17.917 5.16626 17.917C4.75205 17.917 4.41626 18.2528 4.41626 18.667V21.8337C4.41626 23.0763 5.42362 24.0837 6.66626 24.0837H22.3339C23.5766 24.0837 24.5839 23.0763 24.5839 21.8337V18.667C24.5839 18.2528 24.2482 17.917 23.8339 17.917C23.4197 17.917 23.0839 18.2528 23.0839 18.667V21.8337C23.0839 22.2479 22.7482 22.5837 22.3339 22.5837H6.66626C6.25205 22.5837 5.91626 22.2479 5.91626 21.8337V18.667Z" />
                </svg>
            </div>
        @endif

        @if ($fileName)
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $fileName }}</p>
            <p class="mt-1 text-xs text-brand-500">Klik untuk ganti file</p>
        @else
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Klik atau seret file ke sini</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $slot }}</p>
        @endif
    </div>
</div>
