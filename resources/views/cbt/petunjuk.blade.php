<x-layouts.cbt title="Petunjuk Ujian">
    <div x-data="{ sudahBaca: false }" class="mx-auto max-w-xl animate-pbsf-fade-up motion-reduce:animate-none">
        <div class="pbsf-glass relative overflow-hidden p-6 sm:p-8">
            <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain"></div>

            <p class="text-xs font-semibold tracking-[.14em] text-pbsf-gold uppercase">Petunjuk Ujian</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold">{{ $pesertaUjian->ujian->nama }}</h1>
                @if ($pesertaUjian->ujian->pelajaran)
                    <span class="rounded-full border border-pbsf-rain/40 bg-pbsf-rain/15 px-2.5 py-0.5 text-xs font-semibold text-[#86dbcc]">{{ $pesertaUjian->ujian->pelajaran->nama }}</span>
                @endif
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <span class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/[.05] px-3 py-2 text-[#dfe5f5]">
                    <svg class="size-4 text-pbsf-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01" /></svg>
                    {{ $pesertaUjian->ujian->jumlah_soal }} soal
                </span>
                <span class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/[.05] px-3 py-2 font-mono text-[#dfe5f5]">
                    <svg class="size-4 text-pbsf-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" /></svg>
                    {{ gmdate('H:i:s', $pesertaUjian->ujian->durasi_detik) }}
                </span>
            </div>

            {{-- Sistem penilaian --}}
            <p class="mt-7 mb-3 text-sm font-semibold text-[#dfe5f5]">Sistem Penilaian</p>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl border border-pbsf-rain/35 bg-pbsf-rain/10 p-4">
                    <p class="text-3xl font-extrabold text-[#52c2ae]">+9</p>
                    <p class="mt-1 text-xs text-[#aab4d4]">Jawaban Benar<span class="sr-only">: Skor +9</span></p>
                </div>
                <div class="rounded-xl border border-error-500/35 bg-error-500/10 p-4">
                    <p class="text-3xl font-extrabold text-error-400">−1</p>
                    <p class="mt-1 text-xs text-[#aab4d4]">Jawaban Salah<span class="sr-only">: Skor -1</span></p>
                </div>
                <div class="rounded-xl border border-white/15 bg-white/[.05] p-4">
                    <p class="text-3xl font-extrabold text-[#aab4d4]">0</p>
                    <p class="mt-1 text-xs text-[#aab4d4]">Tidak Menjawab<span class="sr-only">: Skor 0</span></p>
                </div>
            </div>

            {{-- Ketentuan --}}
            <p class="mt-7 mb-3 text-sm font-semibold text-[#dfe5f5]">Ketentuan Lain</p>
            <ul class="space-y-2.5 text-sm text-[#c7cfe6]">
                @foreach ([
                    'Waktu berjalan otomatis sejak ujian dimulai dan tidak bisa dijeda.',
                    'Jawaban tersimpan otomatis setiap kamu memilih pilihan.',
                    'Tandai soal dengan "Ragu-ragu" untuk kembali memeriksanya nanti.',
                    'Setelah diselesaikan, jawaban tidak bisa diubah lagi.',
                ] as $ketentuan)
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex size-5 flex-none items-center justify-center rounded-full bg-pbsf-gold/15 text-[11px] font-bold text-pbsf-gold">{{ $loop->iteration }}</span>
                        {{ $ketentuan }}
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 rounded-xl border border-white/10 bg-white/[.04] px-4 py-3 text-xs text-[#aab4d4]">
                <span class="font-semibold text-[#dfe5f5]">Tips keyboard:</span>
                <kbd class="mx-0.5 rounded border border-white/20 bg-white/10 px-1.5 py-0.5 font-mono text-[11px] text-white">←</kbd>
                <kbd class="mx-0.5 rounded border border-white/20 bg-white/10 px-1.5 py-0.5 font-mono text-[11px] text-white">→</kbd> pindah soal,
                <kbd class="mx-0.5 rounded border border-white/20 bg-white/10 px-1.5 py-0.5 font-mono text-[11px] text-white">A</kbd>–<kbd class="mx-0.5 rounded border border-white/20 bg-white/10 px-1.5 py-0.5 font-mono text-[11px] text-white">E</kbd> pilih jawaban,
                <kbd class="mx-0.5 rounded border border-white/20 bg-white/10 px-1.5 py-0.5 font-mono text-[11px] text-white">R</kbd> tandai ragu-ragu.
            </div>

            <label class="mt-7 flex cursor-pointer items-start gap-3 text-sm text-[#dfe5f5] select-none">
                <span class="relative mt-0.5 flex">
                    <input type="checkbox" x-model="sudahBaca"
                        class="peer size-5 cursor-pointer appearance-none rounded-md border border-white/30 bg-white/[.07] transition checked:border-pbsf-gold checked:bg-pbsf-gold focus-visible:ring-4 focus-visible:ring-pbsf-gold/30 focus-visible:outline-hidden" />
                    <svg class="pointer-events-none absolute inset-0 m-auto size-3.5 text-pbsf-navy opacity-0 peer-checked:opacity-100" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                        <path d="M11.67 3.5 5.25 9.92 2.33 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                Saya sudah membaca dan memahami petunjuk di atas.
            </label>

            <form method="POST" action="{{ route('cbt.ujian.mulai', $pesertaUjian) }}" class="mt-6" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <button type="submit" :disabled="!sudahBaca || loading" class="pbsf-btn-gold h-12 w-full text-base">
                    <span x-text="loading ? 'Memulai…' : 'Mulai Ujian'">Mulai Ujian</span>
                    <span x-show="!loading" aria-hidden="true">&rarr;</span>
                </button>
            </form>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('cbt.dashboard') }}" class="pbsf-btn-ghost">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
</x-layouts.cbt>
