@php
    $tidakDijawab = $pesertaUjian->ujian->jumlah_soal - $pesertaUjian->benar - $pesertaUjian->salah;
@endphp

<x-layouts.cbt title="Hasil Ujian">
    @if (session('error'))
        <x-ui.alert variant="error" class="mb-6">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="mx-auto max-w-lg">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-1 flex flex-wrap items-center justify-center gap-2">
                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $pesertaUjian->ujian->nama }}</p>
                @if ($pesertaUjian->ujian->pelajaran)
                    <x-ui.badge color="light" size="sm">{{ $pesertaUjian->ujian->pelajaran->nama }}</x-ui.badge>
                @endif
            </div>
            <p class="text-xs text-gray-400">
                Diselesaikan {{ $pesertaUjian->waktu_selesai->translatedFormat('d M Y, H:i') }}
                @if ($pesertaUjian->durasiPengerjaan())
                    &middot; {{ gmdate('H:i:s', $pesertaUjian->durasiPengerjaan()) }}
                @endif
            </p>

            <div class="my-6 flex flex-col items-center">
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-500/15">
                    <span class="text-title-md font-bold text-brand-500 dark:text-brand-400">{{ $pesertaUjian->nilai }}</span>
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Nilai Kamu</p>
            </div>

            <div class="grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/15">
                    <p class="text-gray-500 dark:text-gray-400">Benar</p>
                    <p class="mt-1 text-lg font-semibold text-success-600 dark:text-success-500">{{ $pesertaUjian->benar }}</p>
                </div>
                <div class="rounded-xl bg-error-50 p-4 dark:bg-error-500/15">
                    <p class="text-gray-500 dark:text-gray-400">Salah</p>
                    <p class="mt-1 text-lg font-semibold text-error-600 dark:text-error-500">{{ $pesertaUjian->salah }}</p>
                </div>
                <div class="rounded-xl bg-gray-100 p-4 dark:bg-white/5">
                    <p class="text-gray-500 dark:text-gray-400">Kosong</p>
                    <p class="mt-1 text-lg font-semibold text-gray-600 dark:text-gray-300">{{ $tidakDijawab }}</p>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-400">Dari {{ $pesertaUjian->ujian->jumlah_soal }} soal</p>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('cbt.dashboard') }}"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                &larr; Kembali ke Dashboard
            </a>
        </div>
    </div>
</x-layouts.cbt>
