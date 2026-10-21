@use('App\Support\LogAktivitas')

<div>
    @php
        $kelasInput = 'h-11 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    @endphp

    <x-common.component-card title="Log Aktivitas" desc="Riwayat login, perubahan data oleh admin, dan aktivitas ujian peserta.">
        @if ($statusMessage)
            <x-ui.alert variant="success" class="mb-4">{{ $statusMessage }}</x-ui.alert>
        @endif

        @if (auth()->user()->hasRole('administrator') && $this->jumlahLogLama > 0)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                <span>Ada {{ $this->jumlahLogLama }} log yang lebih lama dari {{ config('activitylog.delete_records_older_than_days') }} hari.</span>
                <x-ui.button size="sm" variant="outline" wire:click="hapusLogLama"
                    wire:confirm="Hapus {{ $this->jumlahLogLama }} log yang lebih lama dari {{ config('activitylog.delete_records_older_than_days') }} hari? Tindakan ini tidak bisa dibatalkan."
                    wire:loading.attr="disabled" wire:target="hapusLogLama">
                    Hapus Log Lama
                </x-ui.button>
            </div>
        @endif

        <div class="mb-5 flex gap-2 border-b border-gray-200 dark:border-gray-800">
            @foreach (LogAktivitas::LOG as $kunci => $label)
                <button type="button" wire:click="pilihLog('{{ $kunci }}')"
                    class="border-b-2 px-4 py-2.5 text-sm font-medium {{ $log === $kunci ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Dari</label>
                    <input type="date" wire:model.live="dari" class="{{ $kelasInput }} w-40" />
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Sampai</label>
                    <input type="date" wire:model.live="sampai" class="{{ $kelasInput }} w-40" />
                </div>

                <x-form.select wire:model.live="event" placeholder="Semua Aktivitas" wrapper-class="w-56">
                    @foreach (LogAktivitas::EVENT[$log] as $kunci => $label)
                        <option value="{{ $kunci }}">{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama / akun / IP..."
                    class="{{ $kelasInput }} w-64" />
            </div>

            <a href="{{ route('admin.log-aktivitas.export', $this->parameterExport) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                Export Excel
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Waktu</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ $log === LogAktivitas::UJIAN ? 'Peserta' : 'Pelaku' }}</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aktivitas</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Keterangan</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">IP</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->aktivitas as $aktivitas)
                        <tr wire:key="aktivitas-{{ $aktivitas->id }}">
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $aktivitas->created_at->translatedFormat('d M Y, H:i:s') }}
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ LogAktivitas::pelakuDari($aktivitas) }}</td>
                            <td class="px-5 py-3 text-sm">
                                <x-ui.badge size="sm" :color="LogAktivitas::warnaEvent($aktivitas)">{{ LogAktivitas::labelEvent($aktivitas) }}</x-ui.badge>
                                @if (! in_array($aktivitas->event, ['login', 'logout', 'login_gagal'], true))
                                    <p class="mt-1 text-gray-700 dark:text-gray-300">{{ $aktivitas->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ LogAktivitas::keterangan($aktivitas) ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $aktivitas->properties['ip'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-right text-sm">
                                <button type="button" wire:click="lihat({{ $aktivitas->id }})" class="font-medium text-brand-500 hover:text-brand-600">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada log pada rentang & filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->aktivitas->links() }}
        </div>
    </x-common.component-card>

    <x-ui.modal wire-model="showDetail" class="max-w-2xl m-4">
        <div class="max-h-[85vh] overflow-y-auto p-6">
            @if ($aktivitas = $this->detail)
                @php
                    $properti = $aktivitas->properties;
                    $perubahan = collect(array_keys($properti['attributes'] ?? []))
                        ->merge(array_keys($properti['old'] ?? []))
                        ->unique();
                    $lainnya = $properti->except(['attributes', 'old', 'ip', 'user_agent', 'pelaku', 'label']);
                    $tampil = fn ($nilai) => is_array($nilai) ? (array_is_list($nilai) ? implode(', ', $nilai) : json_encode($nilai, JSON_UNESCAPED_UNICODE)) : (is_bool($nilai) ? ($nilai ? 'Ya' : 'Tidak') : ($nilai ?? '—'));
                @endphp

                <h3 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $aktivitas->description }}</h3>
                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">{{ $aktivitas->created_at->translatedFormat('l, d F Y H:i:s') }}</p>

                <dl class="mb-5 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-[8rem_1fr]">
                    <dt class="text-gray-500 dark:text-gray-400">Pelaku</dt>
                    <dd class="text-gray-800 dark:text-white/90">{{ LogAktivitas::pelakuDari($aktivitas) }}</dd>
                    @if ($properti['label'] ?? null)
                        <dt class="text-gray-500 dark:text-gray-400">Data</dt>
                        <dd class="text-gray-800 dark:text-white/90">{{ $properti['label'] }}</dd>
                    @endif
                    <dt class="text-gray-500 dark:text-gray-400">IP</dt>
                    <dd class="font-mono text-gray-800 dark:text-white/90">{{ $properti['ip'] ?? '—' }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">Perangkat</dt>
                    <dd class="break-all text-gray-800 dark:text-white/90">{{ $properti['user_agent'] ?? '—' }}</dd>
                    @foreach ($lainnya as $kunci => $nilai)
                        <dt class="text-gray-500 dark:text-gray-400">{{ str($kunci)->replace('_', ' ')->ucfirst() }}</dt>
                        <dd class="break-all text-gray-800 dark:text-white/90">{{ $kunci === 'alasan' ? (LogAktivitas::ALASAN_GAGAL[$nilai] ?? $nilai) : $tampil($nilai) }}</dd>
                    @endforeach
                </dl>

                @if ($perubahan->isNotEmpty())
                    <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
                        <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kolom</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Sebelum</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Sesudah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($perubahan as $kolom)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $kolom }}</td>
                                        <td class="break-all px-4 py-2 text-gray-700 dark:text-gray-300">{{ $tampil($properti['old'][$kolom] ?? null) }}</td>
                                        <td class="break-all px-4 py-2 text-gray-700 dark:text-gray-300">{{ $tampil($properti['attributes'][$kolom] ?? null) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($aktivitas->event === 'updated')
                    <p class="text-sm text-gray-500 dark:text-gray-400">Isi yang diubah tidak dicatat untuk data ini.</p>
                @endif
            @endif

            <div class="mt-6 flex justify-end">
                <x-ui.button type="button" variant="outline" wire:click="tutupDetail">Tutup</x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
