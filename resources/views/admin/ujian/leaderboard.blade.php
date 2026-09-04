<x-layouts.admin :title="'Leaderboard — '.$ujian->nama" :page-title="'Leaderboard: '.$ujian->nama">
    <div class="mb-4">
        <a href="{{ route('admin.ujian.index') }}" class="text-sm text-brand-500 hover:text-brand-600">&larr; Kembali ke Ujian</a>
    </div>

    <x-common.component-card title="Peringkat Peserta"
        desc="{{ $ujian->jenjang->nama }} · {{ $ujian->pelajaran->nama }} · diurutkan nilai tertinggi, seri diputus oleh waktu pengerjaan tercepat.">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('admin.ujian.leaderboard.export', $ujian) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                Export Leaderboard
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Peringkat</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">No. Registrasi</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Asal Sekolah</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Nilai</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Waktu Pengerjaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($ranking as $i => $siswaUjian)
                        @php $peringkat = $i + 1; @endphp
                        <tr>
                            <td class="px-5 py-3 text-sm">
                                @if ($peringkat <= 3)
                                    <x-ui.badge :color="['success', 'warning', 'light'][$peringkat - 1]">#{{ $peringkat }}</x-ui.badge>
                                @else
                                    <span class="text-gray-600 dark:text-gray-300">#{{ $peringkat }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $siswaUjian->siswa->noreg }}</td>
                            <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $siswaUjian->siswa->nama }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $siswaUjian->siswa->asal_sekolah }}</td>
                            <td class="px-5 py-3 text-sm font-semibold text-gray-800 dark:text-white/90">{{ $siswaUjian->nilai }}</td>
                            <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ gmdate('H:i:s', $siswaUjian->durasiPengerjaan()) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada peserta yang menyelesaikan ujian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-common.component-card>
</x-layouts.admin>
