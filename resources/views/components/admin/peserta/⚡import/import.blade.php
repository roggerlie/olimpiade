<div>
    <x-ui.modal wire-model="showModal" class="max-w-lg m-4">
        <form wire:submit="import" class="p-6">
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Import Peserta dari Excel</h3>
            <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                Kolom yang dibutuhkan: <code>noreg</code> (NISN, 10 digit), <code>nama</code>, <code>jenjang</code>
                (nama jenjang — TK/SD/SLTP/SLTA), <code>asal_sekolah</code>, <code>password</code> (opsional —
                kosongkan untuk default ke noreg, atau isi <code>acak</code> untuk password random), dan
                <code>osains</code>/<code>omtk</code>/<code>obing</code> (0/1 — mencatat minat ikut lomba mapel
                itu, dan langsung mendaftarkan ke ujiannya kalau sudah ada untuk jenjangnya).
                <a href="{{ route('admin.peserta.template') }}" class="text-brand-500 hover:text-brand-600">Unduh template</a>.
            </p>

            @if ($imported !== null)
                <x-ui.alert variant="{{ $imported > 0 ? 'success' : 'warning' }}" class="mb-4">
                    {{ $imported }} peserta berhasil diimport.
                </x-ui.alert>
            @endif

            @if (!empty($generatedPasswords))
                <x-ui.alert variant="warning" class="mb-4" title="Password yang di-random">
                    <ul class="mt-2 max-h-40 list-disc space-y-1 overflow-y-auto pl-4 font-mono">
                        @foreach ($generatedPasswords as $noreg => $password)
                            <li>{{ $noreg }}: {{ $password }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            @if (!empty($importNotes))
                <x-ui.alert variant="info" class="mb-4" title="{{ count($importNotes) }} catatan">
                    <ul class="mt-2 max-h-40 list-disc space-y-1 overflow-y-auto pl-4">
                        @foreach ($importNotes as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
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
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">File (.xlsx, .xls, .csv)</label>
                <x-form.dropzone accept=".xlsx,.xls,.csv" :file-name="$this->fileName" wire:model="file">
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
