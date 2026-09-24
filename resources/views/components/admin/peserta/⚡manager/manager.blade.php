<div>
    <x-common.component-card title="Peserta" desc="Data peserta olimpiade beserta akun login mereka.">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama / no. registrasi..."
                    class="h-11 w-64 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                <x-form.select wire:model.live="filterJenjangId" placeholder="Semua Jenjang" wrapper-class="w-56">
                    @foreach ($this->jenjangPilihan as $jenjang)
                        <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <div class="flex gap-3">
                <x-ui.button variant="outline" wire:click="$dispatch('open-import-modal')">Import Excel</x-ui.button>
                <x-ui.button wire:click="create">+ Tambah Peserta</x-ui.button>
            </div>
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">No. Registrasi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Jenjang</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Asal Sekolah</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Password</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Lomba</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->peserta as $peserta)
                        <tr wire:key="peserta-{{ $peserta->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->noreg }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->jenjang->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->asal_sekolah }}</td>
                            <td class="px-5 py-3 font-mono text-sm text-gray-700 dark:text-gray-300">{{ $peserta->password_plain ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($peserta->pelajaranLomba as $pelajaran)
                                        <x-ui.badge color="light" size="sm">{{ $pelajaran->nama }}</x-ui.badge>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                <x-common.table-actions>
                                    <x-common.dropdown-item wire:click="edit({{ $peserta->id }})">Ubah</x-common.dropdown-item>
                                    <x-common.dropdown-item wire:click="resetPassword({{ $peserta->id }})" wire:confirm="Reset password {{ $peserta->nama }} ke No. Registrasi-nya ({{ $peserta->noreg }})?">Reset Password</x-common.dropdown-item>
                                    <x-common.dropdown-item danger wire:click="delete({{ $peserta->id }})" wire:confirm="Yakin ingin menghapus peserta ini? Akun login &amp; riwayat ujiannya ikut terhapus.">Hapus</x-common.dropdown-item>
                                </x-common.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada peserta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->peserta->links() }}
        </div>
    </x-common.component-card>

    <x-ui.modal wire-model="showModal" class="max-w-lg m-4">
        <form wire:submit="save" class="max-h-[85vh] overflow-y-auto p-6">
            <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $editingId ? 'Ubah Peserta' : 'Tambah Peserta' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">No. Registrasi / NISN (10 digit, dipakai untuk login)</label>
                    <input type="text" wire:model="noreg" maxlength="10"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('noreg') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama</label>
                    <input type="text" wire:model="nama"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

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
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Asal Sekolah</label>
                    <input type="text" wire:model="asalSekolah"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('asalSekolah') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        {{ $editingId ? 'Password Baru (opsional, kosongkan jika tidak diubah)' : 'Password' }}
                    </label>
                    <input type="password" wire:model="password"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('password') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Ikut Lomba <span class="font-normal text-gray-400">(boleh belum ada ujiannya)</span>
                    </label>
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @foreach ($this->pelajaranPilihan as $pelajaran)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="pelajaranLombaIds" value="{{ $pelajaran->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/5" />
                                {{ $pelajaran->nama }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="closeModal">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <livewire:admin.peserta.import />
</div>
