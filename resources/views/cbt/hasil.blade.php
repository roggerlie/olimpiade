@php
    $jumlahSoal = $pesertaUjian->ujian->jumlah_soal;
    $tidakDijawab = $jumlahSoal - $pesertaUjian->benar - $pesertaUjian->salah;

    // nilai is cast to decimal:2 ("27.00"); casting drops the trailing zeros.
    $nilai = (float) $pesertaUjian->nilai;
    $nilaiMaksimal = max(1, $jumlahSoal * 9);
    $persen = max(0, min(100, $nilai / $nilaiMaksimal * 100));
@endphp

<x-layouts.cbt title="Hasil Ujian">
    @if (session('error'))
        <div role="alert" class="mx-auto mb-6 max-w-xl rounded-xl border border-error-500/40 bg-error-500/10 px-4 py-3 text-sm text-error-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="mx-auto max-w-xl animate-pbsf-fade-up motion-reduce:animate-none">
        <div class="pbsf-glass relative overflow-hidden p-6 text-center sm:p-8">
            <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain"></div>
            <div class="pointer-events-none absolute top-24 left-1/2 size-64 -translate-x-1/2 rounded-full bg-pbsf-gold/10 blur-3xl"></div>

            <p class="relative text-xs font-semibold tracking-[.14em] text-pbsf-gold uppercase">Hasil Ujian</p>
            <div class="relative mt-2 flex flex-wrap items-center justify-center gap-2">
                <h1 class="text-xl font-bold">{{ $pesertaUjian->ujian->nama }}</h1>
                @if ($pesertaUjian->ujian->pelajaran)
                    <span class="rounded-full border border-pbsf-rain/40 bg-pbsf-rain/15 px-2.5 py-0.5 text-xs font-semibold text-[#86dbcc]">{{ $pesertaUjian->ujian->pelajaran->nama }}</span>
                @endif
            </div>
            <p class="relative mt-1 text-xs text-[#8e9acb]">
                Diselesaikan {{ $pesertaUjian->waktu_selesai->translatedFormat('d M Y, H:i') }}
                @if ($pesertaUjian->durasiPengerjaan())
                    &middot; {{ gmdate('H:i:s', $pesertaUjian->durasiPengerjaan()) }}
                @endif
            </p>

            {{-- Score ring: fills to nilai / (jumlah soal × 9) while the number counts up --}}
            <div class="relative mx-auto my-8 size-48"
                x-data="{
                    target: {{ $nilai }},
                    persen: {{ round($persen, 2) }},
                    tampil: {{ $nilai }},
                    terisi: {{ round($persen, 2) }},
                    init() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        this.tampil = 0;
                        this.terisi = 0;
                        const mulai = performance.now();
                        const durasi = 1400;
                        const langkah = (t) => {
                            const p = Math.min(1, (t - mulai) / durasi);
                            const eased = 1 - Math.pow(1 - p, 3);
                            this.tampil = Math.round(this.target * eased);
                            this.terisi = this.persen * eased;
                            if (p < 1) requestAnimationFrame(langkah);
                        };
                        requestAnimationFrame(langkah);
                    },
                }">
                <svg class="size-full -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                    <defs>
                        <linearGradient id="nilai-gradient" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#ffd069" />
                            <stop offset="55%" stop-color="#ffb930" />
                            <stop offset="100%" stop-color="#2ba393" />
                        </linearGradient>
                    </defs>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="10" />
                    <circle cx="60" cy="60" r="52" fill="none" stroke="url(#nilai-gradient)" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="326.73" :stroke-dashoffset="326.73 * (1 - terisi / 100)"
                        stroke-dashoffset="{{ 326.73 * (1 - $persen / 100) }}" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-5xl font-extrabold text-pbsf-gold-light" x-text="tampil">{{ $nilai }}</span>
                    <span class="mt-1 text-xs text-[#8e9acb]">dari {{ $nilaiMaksimal }}</span>
                </div>
            </div>
            <p class="relative -mt-4 mb-8 text-sm text-[#aab4d4]">Nilai Kamu</p>

            <div class="relative grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-xl border border-pbsf-rain/35 bg-pbsf-rain/10 p-4">
                    <p class="text-[#aab4d4]">Benar</p>
                    <p class="mt-1 text-2xl font-bold text-[#52c2ae]">{{ $pesertaUjian->benar }}</p>
                </div>
                <div class="rounded-xl border border-error-500/35 bg-error-500/10 p-4">
                    <p class="text-[#aab4d4]">Salah</p>
                    <p class="mt-1 text-2xl font-bold text-error-400">{{ $pesertaUjian->salah }}</p>
                </div>
                <div class="rounded-xl border border-white/15 bg-white/[.05] p-4">
                    <p class="text-[#aab4d4]">Kosong</p>
                    <p class="mt-1 text-2xl font-bold text-[#dfe5f5]">{{ $tidakDijawab }}</p>
                </div>
            </div>

            <p class="relative mt-4 text-xs text-[#8e9acb]">Dari {{ $jumlahSoal }} soal</p>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('cbt.dashboard') }}" class="pbsf-btn-ghost">&larr; Kembali ke Dashboard</a>
        </div>
    </div>

    @if ($nilai > 0)
        @push('scripts')
            <script>
                // Light gold/biru/teal confetti burst — once per attempt per
                // browser session, and never with reduced motion.
                (function () {
                    const kunci = 'pbsf-confetti-{{ $pesertaUjian->id }}';
                    try {
                        if (sessionStorage.getItem(kunci)) return;
                        sessionStorage.setItem(kunci, '1');
                    } catch (e) { /* storage blocked — just celebrate */ }
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                    const warna = ['#ffd069', '#ffb930', '#f5a300', '#2f6fed', '#2ba393', '#ffffff'];
                    const wadah = document.createElement('div');
                    wadah.setAttribute('aria-hidden', 'true');
                    wadah.style.cssText = 'position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:60';
                    document.body.appendChild(wadah);

                    for (let i = 0; i < 90; i++) {
                        const k = document.createElement('span');
                        const ukuran = 6 + Math.random() * 6;
                        k.style.cssText = `position:absolute;top:-20px;left:${Math.random() * 100}%;width:${ukuran}px;height:${ukuran * 0.45}px;background:${warna[i % warna.length]};border-radius:2px`;
                        wadah.appendChild(k);
                        k.animate([
                            { transform: 'translate(0,0) rotate(0deg)', opacity: 1 },
                            { transform: `translate(${(Math.random() - 0.5) * 240}px, ${window.innerHeight + 40}px) rotate(${Math.random() * 720}deg)`, opacity: 0.9 },
                        ], { duration: 2200 + Math.random() * 1600, delay: Math.random() * 500, easing: 'cubic-bezier(.2,.6,.4,1)', fill: 'forwards' });
                    }
                    setTimeout(() => wadah.remove(), 4500);
                })();
            </script>
        @endpush
    @endif
</x-layouts.cbt>
