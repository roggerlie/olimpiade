<div>
    <x-common.component-card title="Ruangan" desc="Ruangan tempat ujian dilaksanakan. Kapasitas hanya sebagai peringatan, tidak membatasi penempatan peserta.">
        @can('master-data.manage')
            <div class="mb-4 flex justify-end">
                <x-ui.button wire:click="create">+ Tambah Ruangan</x-ui.button>
            </div>
        @endcan

        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        @if ($errorMessage)
            <x-ui.alert variant="error" class="mb-4">{{ $errorMessage }}</x-ui.alert>
        @endif

        {{-- No overflow-x-auto: same reason as the Pelajaran tab (the "Aksi"
            dropdown would trigger a spurious vertical scrollbar). --}}
        <div class="rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kapasitas</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Keterangan</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->ruangan as $ruangan)
                        <tr wire:key="ruangan-{{ $ruangan->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $ruangan->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $ruangan->kapasitas }} peserta</td>
                            <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $ruangan->keterangan ?? '—' }}</td>
                            <td class="px-5 py-3 text-right text-sm">
                                @can('master-data.manage')
                                    <x-common.table-actions>
                                        <x-common.dropdown-item wire:click="edit({{ $ruangan->id }})">Ubah</x-common.dropdown-item>
                                        <x-common.dropdown-item danger wire:click="delete({{ $ruangan->id }})" wire:confirm="Yakin ingin menghapus ruangan ini?">Hapus</x-common.dropdown-item>
                                    </x-common.table-actions>
                                @else
                                    <span class="text-gray-300 dark:text-gray-700">—</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada data ruangan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-common.component-card>

    <x-ui.modal wire-model="showModal" class="max-w-md m-4">
        <form wire:submit="save" class="max-h-[85vh] overflow-y-auto p-6">
            <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $editingId ? 'Ubah Ruangan' : 'Tambah Ruangan' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama</label>
                    <input type="text" wire:model="nama" placeholder="mis. Lab Komputer 1"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Kapasitas (jumlah peserta)</label>
                    <input type="number" min="1" wire:model="kapasitas"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('kapasitas') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Keterangan (opsional)</label>
                    <input type="text" wire:model="keterangan" placeholder="mis. Gedung B lantai 2"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('keterangan') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
