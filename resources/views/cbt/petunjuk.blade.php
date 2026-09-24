<x-layouts.cbt title="Petunjuk Ujian">
    <div x-data="{ sudahBaca: false }" class="mx-auto max-w-lg">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $pesertaUjian->ujian->nama }}</p>
                @if ($pesertaUjian->ujian->pelajaran)
                    <x-ui.badge color="light" size="sm">{{ $pesertaUjian->ujian->pelajaran->nama }}</x-ui.badge>
                @endif
            </div>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                {{ $pesertaUjian->ujian->jumlah_soal }} soal &middot; {{ gmdate('H:i:s', $pesertaUjian->ujian->durasi_detik) }}
            </p>

            <x-ui.alert variant="warning" title="Sistem Penilaian" class="mb-5">
                <ul class="list-disc space-y-1.5 pl-4">
                    <li>Jawaban Benar: Skor +9</li>
                    <li>Jawaban Salah: Skor -1</li>
                    <li>Tidak Menjawab: Skor 0</li>
                </ul>
            </x-ui.alert>

            <x-ui.alert variant="info" title="Ketentuan Lain" class="mb-8">
                <ul class="list-disc space-y-1.5 pl-4">
                    <li>Waktu berjalan otomatis sejak ujian dimulai dan tidak bisa dijeda.</li>
                    <li>Jawaban tersimpan otomatis setiap kamu memilih pilihan.</li>
                    <li>Setelah diselesaikan, jawaban tidak bisa diubah lagi.</li>
                </ul>
            </x-ui.alert>

            <label class="mb-8 flex items-start gap-2.5 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" x-model="sudahBaca"
                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/5">
                Saya sudah membaca dan memahami petunjuk di atas.
            </label>

            <form method="POST" action="{{ route('cbt.ujian.mulai', $pesertaUjian) }}">
                @csrf
                <button type="submit" :disabled="!sudahBaca"
                    class="w-full rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-40">
                    Mulai Ujian
                </button>
            </form>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('cbt.dashboard') }}"
                class="inline-flex items-center justify-center gap-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                &larr; Kembali ke Dashboard
            </a>
        </div>
    </div>
</x-layouts.cbt>
