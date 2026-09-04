<div>
    <x-common.component-card title="Bank Soal" desc="Kumpulan soal per jenjang & pelajaran, sumber soal untuk ujian.">
        <div class="mb-4 flex justify-end">
            <x-ui.button wire:click="create">+ Tambah Bank Soal</x-ui.button>
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Jenjang</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Pelajaran</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Jumlah Soal</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->bankSoal as $bankSoal)
                        <tr wire:key="bank-soal-{{ $bankSoal->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $bankSoal->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $bankSoal->jenjang->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $bankSoal->pelajaran->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <x-ui.badge :color="$bankSoal->soal_count > 0 ? 'success' : 'light'">{{ $bankSoal->soal_count }} soal</x-ui.badge>
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                <a href="{{ route('admin.bank-soal.soal', $bankSoal) }}" class="mr-3 text-brand-500 hover:text-brand-600">Kelola Soal</a>
                                <x-common.table-actions>
                                    <x-common.dropdown-item wire:click="edit({{ $bankSoal->id }})">Ubah</x-common.dropdown-item>
                                    <x-common.dropdown-item danger wire:click="delete({{ $bankSoal->id }})" wire:confirm="Yakin ingin menghapus bank soal ini? Semua soal di dalamnya ikut terhapus.">Hapus</x-common.dropdown-item>
                                </x-common.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada bank soal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-common.component-card>

    <x-ui.modal wire-model="showModal" class="max-w-lg m-4">
        <form wire:submit="save" class="p-6">
            <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $editingId ? 'Ubah Bank Soal' : 'Tambah Bank Soal' }}
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenjang</label>
                        <x-form.select wire:model="jenjangId" placeholder="-- Pilih Jenjang --">
                            @foreach ($this->jenjangPilihan as $jenjang)
                                <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                            @endforeach
                        </x-form.select>
                        @error('jenjangId') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Pelajaran</label>
                        <x-form.select wire:model="pelajaranId" placeholder="-- Pilih Pelajaran --">
                            @foreach ($this->pelajaranPilihan as $pelajaran)
                                <option value="{{ $pelajaran->id }}">{{ $pelajaran->nama }}</option>
                            @endforeach
                        </x-form.select>
                        @error('pelajaranId') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama</label>
                    <input type="text" wire:model="nama"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi (opsional)</label>
                    <textarea wire:model="deskripsi" rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                    @error('deskripsi') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
