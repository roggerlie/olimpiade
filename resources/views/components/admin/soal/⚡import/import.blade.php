<div>
    <x-ui.modal wire-model="showModal" class="max-w-lg m-4">
        <form wire:submit="import" class="p-6">
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Import Soal</h3>
            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                <strong class="font-medium text-gray-700 dark:text-gray-300">Excel/CSV</strong> — teks saja, kolom
                <code>pertanyaan</code>, <code>pilihan_a</code>–<code>pilihan_d</code>, <code>pilihan_e</code> (opsional), <code>jawaban</code> (A-E).
                <a href="{{ route('admin.bank-soal.soal.template', $bankSoalId) }}" class="text-brand-500 hover:text-brand-600">Unduh template Excel</a>.
            </p>
            <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                <strong class="font-medium text-gray-700 dark:text-gray-300">Word</strong> — bisa disisipin gambar di pertanyaan & tiap pilihan.
                <a href="{{ route('admin.bank-soal.soal.template-word', $bankSoalId) }}" class="text-brand-500 hover:text-brand-600">Unduh template Word</a>,
                isi tabelnya, jangan bikin dokumen baru dari nol.
            </p>

            @if ($imported !== null)
                <x-ui.alert variant="{{ $imported > 0 ? 'success' : 'warning' }}" class="mb-4">
                    {{ $imported }} soal berhasil diimport.
                </x-ui.alert>
            @endif

            @if (!empty($importErrors))
                <x-ui.alert variant="error" class="mb-4" title="{{ count($importErrors) }} baris gagal">
                    <ul class="mt-2 max-h-40 list-disc space-y-1 overflow-y-auto pl-4">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">File (.xlsx, .xls, .csv, .docx)</label>
                <x-form.dropzone accept=".xlsx,.xls,.csv,.docx" :file-name="$this->fileName" wire:model="file">
                    Maks. 5MB
                </x-form.dropzone>
                <div wire:loading wire:target="file" class="mt-1 text-xs text-gray-400">Mengunggah...</div>
                @error('file') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Tutup</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="import">Import</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
