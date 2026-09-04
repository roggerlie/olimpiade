<div>
    <x-common.component-card title="Pelajaran" desc="Daftar mata pelajaran / mata lomba yang tersedia.">
        <div class="mb-4 flex justify-end">
            <x-ui.button wire:click="create">+ Tambah Pelajaran</x-ui.button>
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->pelajaran as $pelajaran)
                        <tr wire:key="pelajaran-{{ $pelajaran->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $pelajaran->nama }}</td>
                            <td class="px-5 py-3 text-right text-sm">
                                <x-common.table-actions>
                                    <x-common.dropdown-item wire:click="edit({{ $pelajaran->id }})">Ubah</x-common.dropdown-item>
                                    <x-common.dropdown-item danger wire:click="delete({{ $pelajaran->id }})" wire:confirm="Yakin ingin menghapus pelajaran ini?">Hapus</x-common.dropdown-item>
                                </x-common.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada data pelajaran.
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
                {{ $editingId ? 'Ubah Pelajaran' : 'Tambah Pelajaran' }}
            </h3>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama</label>
                <input type="text" wire:model="nama"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
