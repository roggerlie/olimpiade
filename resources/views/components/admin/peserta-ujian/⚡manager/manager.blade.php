<div>
    <x-common.component-card title="Peserta: {{ $this->ujian->nama }}"
        desc="{{ $this->ujian->jenjang->nama }} · {{ $this->ujian->pelajaran->nama }} · {{ $this->totalTerdaftar }}/{{ $this->totalPeserta }} peserta terdaftar">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama / no. registrasi..."
                    class="h-11 w-64 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />

                <x-form.select wire:model.live="filterRuangan" placeholder="Semua Ruangan" wrapper-class="w-56">
                    <option value="tanpa">Belum ada ruangan</option>
                    @foreach ($this->ruanganPilihan as $ruangan)
                        <option value="{{ $ruangan->id }}">{{ $ruangan->nama }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ujian.peserta.export', $this->ujian) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                    Export Nilai
                </a>
                <a href="{{ route('admin.ujian.daftar-hadir', $this->ujian) }}" target="_blank"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                    Cetak Daftar Hadir
                </a>
                <x-ui.button variant="outline" wire:click="bukaBagiOtomatis">Bagi Ruangan Otomatis</x-ui.button>
                <x-ui.button variant="outline" wire:click="resetSemua" wire:confirm="Reset progres SEMUA peserta yang sudah mulai mengerjakan ujian ini? Jawaban &amp; waktu mereka akan dihapus.">
                    Reset Semua Progres
                </x-ui.button>
                <x-ui.button wire:click="daftarkanYangBerminat" wire:confirm="Daftarkan peserta jenjang {{ $this->ujian->jenjang->nama }} yang sudah menyatakan minat lomba {{ $this->ujian->pelajaran->nama }} tapi belum terdaftar ke ujian ini?">
                    Daftarkan Peserta yang Berminat
                </x-ui.button>
            </div>
        </div>

        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        @if ($errorMessage)
            <x-ui.alert variant="error" class="mb-4">{{ $errorMessage }}</x-ui.alert>
        @endif

        {{-- Ringkasan ruangan: only rooms this ujian actually uses. Red = over
            kapasitas, counting peserta of other ujian in the same room at an
            overlapping time too (warning only, never blocked). --}}
        @php $ruanganDipakai = $this->pemakaianRuangan->where('terisi', '>', 0); @endphp
        @if ($ruanganDipakai->isNotEmpty() || $this->totalTanpaRuangan > 0)
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($ruanganDipakai as $p)
                    <a href="{{ route('admin.ujian.daftar-hadir', [$this->ujian, 'ruangan' => $p['ruangan']->id]) }}" target="_blank"
                        title="Cetak daftar hadir {{ $p['ruangan']->nama }}"
                        @class([
                            'rounded-lg px-3 py-2 text-xs ring-1 ring-inset transition hover:opacity-80',
                            'bg-error-50 text-error-700 ring-error-200 dark:bg-error-500/15 dark:text-error-400 dark:ring-error-500/30' => $p['melebihi'],
                            'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-white/[0.03] dark:text-gray-300 dark:ring-gray-700' => ! $p['melebihi'],
                        ])>
                        <span class="font-semibold">{{ $p['ruangan']->nama }}</span>: {{ $p['total'] }}/{{ $p['ruangan']->kapasitas }}
                        @if ($p['melebihi'])
                            · melebihi kapasitas
                        @endif
                        @if ($p['terisiUjianLain'] > 0)
                            <span class="block text-[11px] opacity-80">termasuk {{ $p['terisiUjianLain'] }} peserta {{ implode(', ', $p['ujianLain']) }} di waktu yang sama</span>
                        @endif
                    </a>
                @endforeach

                @if ($this->totalTanpaRuangan > 0)
                    <button type="button" wire:click="$set('filterRuangan', 'tanpa')"
                        class="rounded-lg bg-warning-50 px-3 py-2 text-xs text-warning-700 ring-1 ring-inset ring-warning-200 hover:opacity-80 dark:bg-warning-500/15 dark:text-warning-400 dark:ring-warning-500/30">
                        <span class="font-semibold">Belum ada ruangan</span>: {{ $this->totalTanpaRuangan }} peserta
                    </button>
                @endif
            </div>
        @endif

        @if ($selectedIds)
            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                <span>{{ count($selectedIds) }} peserta dicentang</span>
                <x-form.select wire:model="targetRuangan" placeholder="-- Pilih ruangan tujuan --" wrapper-class="w-60">
                    @foreach ($this->ruanganPilihan as $ruangan)
                        <option value="{{ $ruangan->id }}">{{ $ruangan->nama }}</option>
                    @endforeach
                    <option value="kosongkan">(Kosongkan ruangan)</option>
                </x-form.select>
                <x-ui.button size="sm" wire:click="pindahkanTerpilih" wire:loading.attr="disabled" wire:target="pindahkanTerpilih">Pindahkan</x-ui.button>
                <button type="button" wire:click="$set('selectedIds', [])" class="font-medium underline hover:no-underline">Batalkan pilihan</button>
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="w-10 py-3 pl-5">
                            @if ($this->idsTerdaftarDiHalaman)
                                <input type="checkbox" wire:click="toggleSemuaDiHalaman" @checked($this->semuaDiHalamanTerpilih) aria-label="Centang semua peserta terdaftar di halaman ini"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/5" />
                            @endif
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">No. Registrasi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ruangan</th>
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
                            <td class="w-10 py-3 pl-5">
                                @if ($pesertaUjian)
                                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $peserta->id }}" aria-label="Centang {{ $peserta->nama }}"
                                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/5" />
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->noreg }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $peserta->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($pesertaUjian)
                                    <select wire:change="pindahkanRuangan({{ $peserta->id }}, $event.target.value)" aria-label="Ruangan {{ $peserta->nama }}"
                                        class="h-9 w-44 rounded-lg border border-gray-300 bg-transparent px-2 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="" @selected(! $pesertaUjian->ruangan_id)>— Belum ada —</option>
                                        @foreach ($this->ruanganPilihan as $ruangan)
                                            <option value="{{ $ruangan->id }}" @selected($pesertaUjian->ruangan_id === $ruangan->id)>{{ $ruangan->nama }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
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
                            <td colspan="9" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($filterRuangan !== '')
                                    Tidak ada peserta di ruangan ini.
                                @else
                                    Tidak ada peserta jenjang {{ $this->ujian->jenjang->nama }}.
                                @endif
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

    <x-ui.modal wire-model="showBagiModal" class="max-w-lg m-4">
        <form wire:submit="bagiOtomatis" class="max-h-[85vh] overflow-y-auto p-6">
            <h3 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Bagi Ruangan Otomatis</h3>
            <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                {{ $this->totalTanpaRuangan }} peserta terdaftar yang belum punya ruangan akan dibagi ke ruangan yang dipilih,
                diisi berurutan sampai kapasitasnya penuh. Peserta yang sudah punya ruangan tidak dipindahkan.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Ruangan yang dipakai</label>
                    <div class="space-y-2">
                        @forelse ($this->pemakaianRuangan as $p)
                            <label wire:key="bagi-ruangan-{{ $p['ruangan']->id }}" class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="bagiRuanganIds" value="{{ $p['ruangan']->id }}"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/5" />
                                <span>
                                    {{ $p['ruangan']->nama }}
                                    <span @class(['text-xs', 'text-error-500' => $p['sisa'] === 0, 'text-gray-400' => $p['sisa'] > 0])>
                                        — sisa {{ $p['sisa'] }} dari {{ $p['ruangan']->kapasitas }}
                                        @if ($p['terisiUjianLain'] > 0)
                                            ({{ $p['terisiUjianLain'] }} kursi dipakai {{ implode(', ', $p['ujianLain']) }} di waktu yang sama)
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada ruangan. Tambahkan dulu di Master Data → Ruangan.</p>
                        @endforelse
                    </div>
                    @error('bagiRuanganIds') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Urutan</label>
                    <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        <label class="flex items-start gap-2">
                            <input type="radio" wire:model="bagiUrutan" value="{{ \App\Services\PenempatanRuanganService::URUTAN_CAMPUR_SEKOLAH }}" class="mt-0.5 h-4 w-4 text-brand-500 focus:ring-brand-500" />
                            <span>Campur asal sekolah <span class="text-xs text-gray-400">— peserta dari sekolah yang sama dipisah sebisa mungkin</span></span>
                        </label>
                        <label class="flex items-start gap-2">
                            <input type="radio" wire:model="bagiUrutan" value="{{ \App\Services\PenempatanRuanganService::URUTAN_NOREG }}" class="mt-0.5 h-4 w-4 text-brand-500 focus:ring-brand-500" />
                            <span>Urut No. Registrasi</span>
                        </label>
                    </div>
                    @error('bagiUrutan') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" wire:click="$set('showBagiModal', false)">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="bagiOtomatis">Bagi Sekarang</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
