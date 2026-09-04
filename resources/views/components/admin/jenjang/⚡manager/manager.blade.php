<div>
    <x-common.component-card title="Jenjang" desc="Kategori tingkat pendidikan peserta (SD, SMP, SMA, dsb).">
        <div class="mb-4 flex justify-end">
            <x-ui.button wire:click="create">+ Tambah Jenjang</x-ui.button>
        </div>

        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        @if ($errorMessage)
            <x-ui.alert variant="error" class="mb-4">{{ $errorMessage }}</x-ui.alert>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->jenjang as $jenjang)
                        <tr wire:key="jenjang-{{ $jenjang->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $jenjang->kode }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $jenjang->nama }}</td>
                            <td class="px-5 py-3 text-right text-sm">
                                <button wire:click="edit({{ $jenjang->id }})" class="mr-3 text-brand-500 hover:text-brand-600">Ubah</button>
                                <button wire:click="delete({{ $jenjang->id }})" wire:confirm="Yakin ingin menghapus jenjang ini?" class="text-error-500 hover:text-error-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada data jenjang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-common.component-card>

    <x-ui.modal wire-model="showModal" class="max-w-md m-4">
        <form wire:submit="save" class="p-6">
            <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $editingId ? 'Ubah Jenjang' : 'Tambah Jenjang' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Kode</label>
                    <input type="text" wire:model="kode" maxlength="2"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm uppercase text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('kode') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama</label>
                    <input type="text" wire:model="nama"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
