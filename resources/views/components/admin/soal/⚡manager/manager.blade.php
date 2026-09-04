<div>
    <div class="mb-4 flex justify-end">
        <x-ui.button wire:click="create">+ Tambah Soal</x-ui.button>
    </div>

    @if ($statusMessage)
        <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
    @endif

    @if ($errorMessage)
        <x-ui.alert variant="error" class="mb-4">{{ $errorMessage }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        @forelse ($this->soal as $soal)
            <div wire:key="soal-{{ $soal->id }}" x-data="{ open: false }"
                class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">#{{ $loop->iteration + ($this->soal->currentPage() - 1) * $this->soal->perPage() }}</span>
                            <x-ui.badge color="success">Jawaban: {{ $soal->jawaban }}</x-ui.badge>
                        </div>
                        <p class="text-sm text-gray-800 dark:text-white/90">{{ Str::limit($soal->pertanyaan, 150) }}</p>
                    </div>

                    <div class="flex shrink-0 items-center gap-3 text-sm">
                        <button @click="open = !open" class="text-gray-500 hover:text-gray-700 dark:text-gray-400" x-text="open ? 'Sembunyikan' : 'Lihat Pilihan'"></button>
                        <button wire:click="edit({{ $soal->id }})" class="text-brand-500 hover:text-brand-600">Ubah</button>
                        <button wire:click="delete({{ $soal->id }})" wire:confirm="Yakin ingin menghapus soal ini?" class="text-error-500 hover:text-error-600">Hapus</button>
                    </div>
                </div>

                <div x-show="open" x-cloak class="mt-4 space-y-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $soal->pertanyaan }}</p>
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($soal->pilihan() as $huruf => $teks)
                            <li class="flex items-start gap-2 rounded-lg px-3 py-2 text-sm {{ $huruf === $soal->jawaban ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : 'text-gray-600 dark:text-gray-400' }}">
                                <span class="font-semibold">{{ $huruf }}.</span>
                                <span>{{ $teks }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                Belum ada soal di bank ini.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->soal->links() }}
    </div>

    <x-ui.modal wire-model="showModal" class="max-w-3xl m-4">
        <form wire:submit="save" class="max-h-[85vh] overflow-y-auto p-6">
            <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $editingId ? 'Ubah Soal' : 'Tambah Soal' }}
            </h3>
            <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                Klik huruf di samping pilihan untuk menandainya sebagai jawaban benar.
            </p>

            <div class="space-y-5">
                <div x-data="{
                    resize(el) {
                        el.style.height = 'auto';
                        el.style.height = el.scrollHeight + 'px';
                    }
                }" x-init="resize($el.querySelector('textarea'))">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Pertanyaan</label>
                    <textarea wire:model="pertanyaan" rows="3" @input="resize($el)"
                        class="w-full resize-none overflow-hidden rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                    @error('pertanyaan') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach (['pilihA' => 'A', 'pilihB' => 'B', 'pilihC' => 'C', 'pilihD' => 'D'] as $field => $huruf)
                            <div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="tandaiJawaban('{{ $huruf }}')"
                                        title="Tandai {{ $huruf }} sebagai jawaban benar"
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border-2 text-sm font-semibold transition
                                            {{ $jawaban === $huruf
                                                ? 'border-success-500 bg-success-500 text-white'
                                                : 'border-gray-300 text-gray-500 hover:border-success-500 hover:text-success-500 dark:border-gray-700 dark:text-gray-400' }}">
                                        {{ $huruf }}
                                    </button>
                                    <input type="text" wire:model="{{ $field }}" placeholder="Teks pilihan {{ $huruf }}"
                                        class="h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90
                                            {{ $jawaban === $huruf
                                                ? 'border-success-500 focus:border-success-500 focus:ring-success-500/10'
                                                : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700' }}" />
                                </div>
                                @error($field) <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    @error('jawaban') <p class="mt-3 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="tandaiJawaban('E')"
                            title="Tandai E sebagai jawaban benar"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border-2 text-sm font-semibold transition
                                {{ $jawaban === 'E'
                                    ? 'border-success-500 bg-success-500 text-white'
                                    : 'border-gray-300 text-gray-500 hover:border-success-500 hover:text-success-500 dark:border-gray-700 dark:text-gray-400' }}">
                            E
                        </button>
                        <input type="text" wire:model="pilihE" placeholder="Teks pilihan E (opsional)"
                            class="h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden focus:ring-3 dark:bg-gray-900 dark:text-white/90
                                {{ $jawaban === 'E'
                                    ? 'border-success-500 focus:border-success-500 focus:ring-success-500/10'
                                    : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700' }}" />
                    </div>
                    @error('pilihE') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
