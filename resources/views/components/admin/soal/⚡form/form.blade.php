<div class="mx-auto max-w-3xl">
    <x-common.component-card :title="$soalId ? 'Ubah Soal' : 'Tambah Soal'"
        desc="Klik huruf di samping pilihan untuk menandainya sebagai jawaban benar. Gambar bisa disisipkan langsung di dalam teks lewat tombol gambar pada masing-masing kotak.">

        <form wire:submit="save" class="space-y-5">
            <div>
                <x-admin.soal.rich-editor id="soal-pertanyaan" wire-model="pertanyaan" label="Pertanyaan"
                    :upload-url="route('admin.bank-soal.soal.upload-gambar', $bankSoalId)" />
                @error('pertanyaan') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach (['pilihA' => 'A', 'pilihB' => 'B', 'pilihC' => 'C', 'pilihD' => 'D'] as $field => $huruf)
                        <div>
                            <div class="mb-1.5 flex items-center gap-2">
                                <button type="button" wire:click="tandaiJawaban('{{ $huruf }}')"
                                    title="Tandai {{ $huruf }} sebagai jawaban benar"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border-2 text-sm font-semibold transition
                                        {{ $jawaban === $huruf
                                            ? 'border-success-500 bg-success-500 text-white'
                                            : 'border-gray-300 text-gray-500 hover:border-success-500 hover:text-success-500 dark:border-gray-700 dark:text-gray-400' }}">
                                    {{ $huruf }}
                                </button>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Pilihan {{ $huruf }}</span>
                            </div>
                            <x-admin.soal.rich-editor id="soal-pilih-{{ strtolower($huruf) }}" :wire-model="$field" :height="140"
                                :upload-url="route('admin.bank-soal.soal.upload-gambar', $bankSoalId)" />
                            @error($field) <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                @error('jawaban') <p class="mt-3 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <button type="button" wire:click="tandaiJawaban('E')"
                        title="Tandai E sebagai jawaban benar"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border-2 text-sm font-semibold transition
                            {{ $jawaban === 'E'
                                ? 'border-success-500 bg-success-500 text-white'
                                : 'border-gray-300 text-gray-500 hover:border-success-500 hover:text-success-500 dark:border-gray-700 dark:text-gray-400' }}">
                        E
                    </button>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-400">Pilihan E (opsional)</span>
                </div>
                <x-admin.soal.rich-editor id="soal-pilih-e" wire-model="pilihE" :height="140"
                    :upload-url="route('admin.bank-soal.soal.upload-gambar', $bankSoalId)" />
                @error('pilihE') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
                <a href="{{ route('admin.bank-soal.soal', $bankSoalId) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300">
                    Batal
                </a>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-common.component-card>
</div>
