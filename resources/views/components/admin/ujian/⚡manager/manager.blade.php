<div>
    <x-common.component-card title="Ujian" desc="Jadwal ujian per bank soal — durasi dihitung otomatis dari sesi mulai & selesai.">
        <div class="mb-4 flex justify-end">
            <x-ui.button wire:click="create">+ Tambah Ujian</x-ui.button>
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ujian</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Jenjang / Pelajaran</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Sesi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Durasi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->ujian as $ujian)
                        <tr wire:key="ujian-{{ $ujian->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <div class="font-medium text-gray-800 dark:text-white/90">{{ $ujian->nama }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $ujian->bankSoal->nama }} · {{ $ujian->jumlah_soal }} soal</div>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $ujian->jenjang->nama }} · {{ $ujian->pelajaran->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $ujian->sesi_mulai->translatedFormat('d M Y, H:i') }} &ndash; {{ $ujian->sesi_selesai->translatedFormat('H:i') }}
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $ujian->sesi_mulai->diffForHumans($ujian->sesi_selesai, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                            </td>
                            <td class="px-5 py-3 text-sm">
                                @if (now()->lt($ujian->sesi_mulai))
                                    <x-ui.badge color="warning">Akan Datang</x-ui.badge>
                                @elseif ($ujian->sesiSedangBerlangsung())
                                    <x-ui.badge color="success">Berlangsung</x-ui.badge>
                                @else
                                    <x-ui.badge color="light">Selesai</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                <a href="{{ route('admin.ujian.peserta', $ujian) }}" class="mr-3 text-brand-500 hover:text-brand-600">Peserta</a>
                                <x-common.table-actions>
                                    <x-common.dropdown-item as="a" href="{{ route('admin.ujian.leaderboard', $ujian) }}">Leaderboard</x-common.dropdown-item>
                                    <x-common.dropdown-item wire:click="edit({{ $ujian->id }})">Ubah</x-common.dropdown-item>
                                    <x-common.dropdown-item danger wire:click="delete({{ $ujian->id }})" wire:confirm="Yakin ingin menghapus ujian ini?">Hapus</x-common.dropdown-item>
                                </x-common.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada ujian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->ujian->links() }}
        </div>
    </x-common.component-card>

    <x-ui.modal wire-model="showModal" class="max-w-lg m-4">
        <form wire:submit="save" class="max-h-[85vh] overflow-y-auto p-6">
            <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $editingId ? 'Ubah Ujian' : 'Tambah Ujian' }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bank Soal</label>
                    <x-form.select wire:model="bankSoalId" placeholder="-- Pilih Bank Soal --">
                        @foreach ($this->bankSoalPilihan as $bankSoal)
                            <option value="{{ $bankSoal->id }}">
                                {{ $bankSoal->nama }} ({{ $bankSoal->jenjang->nama }} · {{ $bankSoal->pelajaran->nama }} · {{ $bankSoal->soal_count }} soal)
                            </option>
                        @endforeach
                    </x-form.select>
                    <p class="mt-1 text-xs text-gray-400">Jenjang &amp; pelajaran ujian mengikuti bank soal yang dipilih.</p>
                    @error('bankSoalId') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Ujian</label>
                    <input type="text" wire:model="nama"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('nama') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jumlah Soal per Peserta</label>
                    <input type="number" min="1" wire:model="jumlahSoal"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('jumlahSoal') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.date-picker id="ujian-sesi-mulai" label="Sesi Mulai" enable-time date-format="Y-m-d\TH:i"
                            wire:model="sesiMulai" placeholder="Pilih tanggal & jam mulai" />
                        @error('sesiMulai') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-form.date-picker id="ujian-sesi-selesai" label="Sesi Selesai" enable-time date-format="Y-m-d\TH:i"
                            wire:model="sesiSelesai" placeholder="Pilih tanggal & jam selesai" />
                        @error('sesiSelesai') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                    </div>
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
