<div>
    <x-common.component-card title="Peserta: {{ $this->ujian->nama }}"
        desc="{{ $this->ujian->jenjang->nama }} · {{ $this->ujian->pelajaran->nama }} · {{ $this->totalTerdaftar }}/{{ $this->totalPeserta }} peserta terdaftar">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama / no. registrasi..."
                class="h-11 w-64 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

            <div class="flex gap-3">
                <a href="{{ route('admin.ujian.peserta.export', $this->ujian) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                    Export Nilai
                </a>
                <x-ui.button variant="outline" wire:click="resetSemua" wire:confirm="Reset progres SEMUA peserta yang sudah mulai mengerjakan ujian ini? Jawaban &amp; waktu mereka akan dihapus.">
                    Reset Semua Progres
                </x-ui.button>
                <x-ui.button wire:click="daftarkanSemua" wire:confirm="Daftarkan semua peserta jenjang {{ $this->ujian->jenjang->nama }} yang belum terdaftar ke ujian ini?">
                    Daftarkan Semua Peserta {{ $this->ujian->jenjang->nama }}
                </x-ui.button>
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Waktu Mulai</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Waktu Selesai</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nilai</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->peserta as $peserta)
                        @php $pesertaUjian = $peserta->pesertaUjian->first(); @endphp
                        <tr wire:key="peserta-ujian-{{ $peserta->id }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->noreg }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->nama }}</td>
                            <td class="px-5 py-3 text-sm">
                                @if (!$pesertaUjian)
                                    <x-ui.badge color="light">Belum Terdaftar</x-ui.badge>
                                @elseif ($pesertaUjian->sudahSubmit())
                                    <x-ui.badge color="success">Selesai</x-ui.badge>
                                @elseif ($pesertaUjian->waktu_mulai)
                                    <x-ui.badge color="warning">Sedang Mengerjakan</x-ui.badge>
                                @else
                                    <x-ui.badge color="primary">Terdaftar</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $pesertaUjian?->waktu_mulai?->translatedFormat('d M Y, H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $pesertaUjian?->waktu_selesai?->translatedFormat('d M Y, H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $pesertaUjian?->sudahSubmit() ? $pesertaUjian->nilai : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm whitespace-nowrap">
                                @if (!$pesertaUjian)
                                    <button wire:click="toggleDaftar({{ $peserta->id }})" class="text-brand-500 hover:text-brand-600">Daftarkan</button>
                                @else
                                    @if (!$pesertaUjian->waktu_mulai)
                                        <button wire:click="toggleDaftar({{ $peserta->id }})" wire:confirm="Batalkan pendaftaran {{ $peserta->nama }}?" class="mr-3 text-error-500 hover:text-error-600">Batalkan</button>
                                    @else
                                        <button wire:click="resetProgres({{ $peserta->id }})" wire:confirm="Reset progres {{ $peserta->nama }}? Jawaban &amp; waktu yang sudah tercatat akan dihapus." class="text-error-500 hover:text-error-600">Reset</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada peserta jenjang {{ $this->ujian->jenjang->nama }}.
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
</div>
