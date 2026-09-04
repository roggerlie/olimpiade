<x-layouts.cbt title="Hasil Ujian">
    @if (session('error'))
        <x-ui.alert variant="error" class="mb-6">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="mx-auto max-w-lg rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pesertaUjian->ujian->nama }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pesertaUjian->ujian->pelajaran->nama ?? '' }}</p>

        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">Nilai Kamu</p>
        <p class="text-title-md font-bold text-gray-800 dark:text-white/90">{{ $pesertaUjian->nilai }}</p>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/15">
                <p class="text-gray-500 dark:text-gray-400">Benar</p>
                <p class="text-lg font-semibold text-success-600 dark:text-success-500">{{ $pesertaUjian->benar }}</p>
            </div>
            <div class="rounded-xl bg-error-50 p-4 dark:bg-error-500/15">
                <p class="text-gray-500 dark:text-gray-400">Salah</p>
                <p class="text-lg font-semibold text-error-600 dark:text-error-500">{{ $pesertaUjian->salah }}</p>
            </div>
        </div>

        <p class="mt-6 text-xs text-gray-400">
            Dikumpulkan {{ $pesertaUjian->waktu_selesai->translatedFormat('d M Y, H:i') }}
        </p>

        <a href="{{ route('cbt.dashboard') }}" class="mt-6 inline-block text-sm text-brand-500 hover:text-brand-600">
            &larr; Kembali ke Dashboard
        </a>
    </div>
</x-layouts.cbt>
