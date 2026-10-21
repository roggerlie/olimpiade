<div class="space-y-6">
    @php
        $kelasInput = 'h-11 w-40 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
        $kelasTh = 'px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400';
    @endphp

    <x-common.component-card title="Deteksi Login Ganda" desc="Petunjuk awal kemungkinan joki dari log login peserta — bukan bukti. Peserta yang pindah komputer karena rusak juga akan muncul di sini.">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Dari</label>
                <input type="date" wire:model.live="dari" class="{{ $kelasInput }}" />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Sampai</label>
                <input type="date" wire:model.live="sampai" class="{{ $kelasInput }}" />
            </div>
        </div>
    </x-common.component-card>

    <x-common.component-card title="Satu Peserta, Banyak IP / Perangkat" desc="Peserta yang login dari lebih dari satu alamat IP atau perangkat (browser) pada rentang tanggal ini.">
        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="{{ $kelasTh }}">Peserta</th>
                        <th class="{{ $kelasTh }}">Jumlah Login</th>
                        <th class="{{ $kelasTh }}">IP</th>
                        <th class="{{ $kelasTh }}">Perangkat</th>
                        <th class="{{ $kelasTh }}">Login Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->pesertaBanyakPerangkat as $baris)
                        <tr wire:key="peserta-{{ $loop->index }}">
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $baris['pelaku'] }}
                                @if ($baris['peserta'])
                                    <p class="text-xs text-gray-400">{{ $baris['peserta']->asal_sekolah }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $baris['jumlahLogin'] }}×</td>
                            <td class="px-5 py-3 text-sm">
                                <x-ui.badge size="sm" :color="count($baris['ip']) > 1 ? 'warning' : 'light'">{{ count($baris['ip']) }} IP</x-ui.badge>
                                <p class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{{ implode(', ', $baris['ip']) }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm">
                                <x-ui.badge size="sm" :color="count($baris['perangkat']) > 1 ? 'warning' : 'light'">{{ count($baris['perangkat']) }} perangkat</x-ui.badge>
                                @foreach ($baris['perangkat'] as $perangkat)
                                    <p class="mt-1 max-w-md truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $perangkat }}">{{ $perangkat }}</p>
                                @endforeach
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $baris['terakhir']->translatedFormat('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada peserta yang login dari lebih dari satu IP / perangkat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-common.component-card>

    <x-common.component-card title="Satu Perangkat, Banyak Peserta" desc="IP + perangkat yang sama dipakai login oleh lebih dari satu peserta. Wajar terjadi jika satu komputer lab dipakai bergantian di sesi berbeda, atau komputer lab yang identik di belakang satu jaringan — cek jam loginnya.">
        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="{{ $kelasTh }}">IP</th>
                        <th class="{{ $kelasTh }}">Perangkat</th>
                        <th class="{{ $kelasTh }}">Peserta</th>
                        <th class="{{ $kelasTh }}">Login Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->perangkatBanyakPeserta as $baris)
                        <tr wire:key="perangkat-{{ $loop->index }}">
                            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $baris['ip'] }}</td>
                            <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
                                <p class="max-w-xs truncate" title="{{ $baris['perangkat'] }}">{{ $baris['perangkat'] }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <x-ui.badge size="sm" color="warning">{{ count($baris['pelaku']) }} peserta</x-ui.badge>
                                <p class="mt-1">{{ implode(', ', $baris['pelaku']) }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $baris['terakhir']->translatedFormat('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada perangkat yang dipakai lebih dari satu peserta.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-common.component-card>
</div>
