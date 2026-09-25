<x-layouts.cbt :title="$pesertaUjian->ujian->nama">
    @push('head')
        {{-- Renders WIRIS MathType formulas saved by resources/js/soal-editor.js
            (see admin/bank-soal/soal-create.blade.php) — a plain <img> with a
            data:image/svg+xml src already displays correctly on its own, but
            this WIRIS-provided script upgrades it (better scaling/
            accessibility). Cloud-hosted and free to just display formulas
            (no account needed), separate from the paid/metered editor. --}}
        <script src="https://www.wiris.net/demo/plugins/app/WIRISplugins.js?viewer=image"></script>
    @endpush
    <div
        x-data="ujianApp({
            soal: @js($soal),
            mulai: {{ $pesertaUjian->waktu_mulai->timestamp * 1000 }},
            deadline: {{ $batasWaktu->timestamp * 1000 }},
            jawabUrl: '{{ route('cbt.ujian.jawab', $pesertaUjian) }}',
            submitUrl: '{{ route('cbt.ujian.submit', $pesertaUjian) }}',
            raguKey: 'pbsf-ragu-{{ $pesertaUjian->id }}',
        })"
        x-init="init()"
        @keydown.window="onKey($event)"
        class="mx-auto max-w-6xl"
    >
        {{-- Exam name + live countdown + submit button. The whole bar turns
            red in the last 5 minutes — the "time's almost up" cue isn't just
            the numbers inside it. --}}
        <div class="pbsf-glass relative mb-4 overflow-hidden p-4 transition-colors duration-700"
            :class="remaining <= 300 ? 'border-error-500/50 bg-error-500/15' : ''">
            <div class="absolute inset-x-0 top-0 h-1 transition-colors duration-700"
                :class="remaining <= 300 ? 'bg-error-500' : 'bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain'"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-pbsf-gold-light to-pbsf-gold-deep text-pbsf-navy">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 19.5V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14.5" /><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5H6.5A2.5 2.5 0 0 0 4 19.5Z" /><path d="M9 7h6M9 11h4" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-bold">{{ $pesertaUjian->ujian->nama }}</p>
                        <p class="text-xs text-[#aab4d4]">{{ $pesertaUjian->ujian->pelajaran->nama ?? '' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2.5 rounded-xl border px-3.5 py-2 transition-colors"
                        :class="remaining <= 300 ? 'border-error-500/50 bg-error-500/20' : 'border-pbsf-gold/30 bg-pbsf-gold/10'">
                        <svg class="size-5" :class="remaining <= 300 ? 'text-error-300 animate-pulse' : 'text-pbsf-gold'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="13" r="8" /><path d="M12 9v4l2 2M9 2h6" />
                        </svg>
                        <div>
                            <p class="text-[10px] font-semibold tracking-[.12em] text-[#aab4d4] uppercase">Sisa Waktu</p>
                            <p class="font-mono text-xl leading-none font-bold tabular-nums"
                                :class="remaining <= 300 ? 'text-error-300 animate-pulse motion-reduce:animate-none' : 'text-pbsf-gold-light'"
                                x-text="formatWaktu()"></p>
                        </div>
                    </div>

                    <button type="button" @click="submit()" :disabled="submitting" class="pbsf-btn-gold h-12">
                        Selesaikan
                    </button>
                </div>
            </div>

            {{-- Visual time-remaining bar: drains left-to-right. --}}
            <div class="relative mt-3 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full transition-[width] duration-1000 ease-linear"
                    :class="remaining <= 300 ? 'bg-error-500' : 'bg-linear-to-r from-pbsf-gold to-pbsf-rain'"
                    :style="`width: ${progressPercent()}%`"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_280px]">
            {{-- Question "paper": deliberately light on the dark stage — MathType
                formulas and uploaded images are black-on-white, and would vanish
                on a dark card. --}}
            <div class="overflow-hidden rounded-2xl bg-[#fbfaf6] text-gray-800 shadow-[0_30px_80px_rgba(0,0,0,.45)] ring-1 ring-white/10">
                <div class="h-1 bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain"></div>
                <div class="p-5 sm:p-7">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="rounded-lg bg-pbsf-navy px-3 py-1.5 text-sm font-bold text-white">
                                Soal <span x-text="index + 1"></span><span class="font-medium text-white/60"> / <span x-text="soal.length"></span></span>
                            </span>

                            <span x-show="currentSoal().status === 'saving'" x-cloak class="text-xs text-gray-400">Menyimpan…</span>
                            <span x-show="currentSoal().status === 'saved' || (!currentSoal().status && currentSoal().jawabanSaya)" x-cloak class="flex items-center gap-1 text-xs font-medium text-pbsf-rain-deep">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7" /></svg>
                                Tersimpan
                            </span>
                            <span x-show="currentSoal().status === 'error'" x-cloak role="alert" class="flex items-center gap-2 rounded-lg bg-error-50 px-2.5 py-1 text-xs font-medium text-error-700">
                                <span x-text="currentSoal().errorMessage"></span>
                                <button type="button" @click="simpan(currentSoal())" class="font-bold underline underline-offset-2 hover:text-error-800">Coba lagi</button>
                            </span>
                        </div>

                        <button type="button" @click="toggleRagu()"
                            class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                            :class="isRagu(currentSoal()) ? 'border-pbsf-gold-deep bg-pbsf-gold text-pbsf-navy' : 'border-gray-300 text-gray-600 hover:border-pbsf-gold-deep hover:text-[#855600]'"
                            :aria-pressed="isRagu(currentSoal())">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V4h11l-2 4 2 4H5" /></svg>
                            Ragu-ragu
                        </button>
                    </div>

                    {{-- Keying this block on `index` forces Alpine to tear it down
                        and remount it whenever the question changes, which is
                        what makes the enter transition below actually replay per
                        question — a plain reactive x-text update wouldn't. --}}
                    <template x-for="_ in [index]" :key="index">
                        <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-1" x-transition:enter-end="opacity-100 translate-x-0">
                            <div class="mb-6 text-base leading-relaxed text-gray-800 [&_img]:mt-2 [&_img]:max-h-64 [&_img]:rounded-lg [&_img]:border [&_img]:border-gray-200 [&_p]:mb-2 [&_p:last-child]:mb-0"
                                x-html="currentSoal().pertanyaan"></div>

                            <div class="space-y-3">
                                <template x-for="(html, huruf) in currentSoal().pilihan" :key="huruf">
                                    <button type="button" @click="pilih(huruf)"
                                        class="flex w-full items-start gap-3 rounded-xl border-2 p-4 text-left text-sm transition hover:-translate-y-px"
                                        :class="currentSoal().jawabanSaya === huruf
                                            ? 'border-pbsf-gold-deep bg-[#fff6e0] text-gray-900 shadow-[0_6px_18px_rgba(245,163,0,.18)]'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-pbsf-gold/60 hover:bg-[#fffbf0]'">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold transition"
                                            :class="currentSoal().jawabanSaya === huruf ? 'border-pbsf-gold-deep bg-pbsf-gold-deep text-pbsf-navy' : 'border-gray-300 text-gray-500'"
                                            x-text="huruf"></span>
                                        <span class="flex-1 pt-0.5 [&_img]:mt-1 [&_img]:max-h-32 [&_img]:rounded-lg [&_img]:border [&_img]:border-gray-200 [&_p]:mb-1 [&_p:last-child]:mb-0"
                                            x-html="html"></span>
                                        <template x-if="currentSoal().jawabanSaya === huruf">
                                            <svg class="mt-1 size-4 shrink-0 text-pbsf-gold-deep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7" /></svg>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="mt-7 flex items-center justify-between gap-3 border-t border-gray-200 pt-5">
                        <button type="button" @click="prev()" :disabled="index === 0"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent">
                            &larr; Sebelumnya
                        </button>
                        <p class="hidden text-xs text-gray-400 sm:block">Keyboard: ← → pindah · A–E pilih · R ragu-ragu</p>
                        <button type="button" @click="next()" :disabled="index === soal.length - 1"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-pbsf-navy px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#1c356f] disabled:cursor-not-allowed disabled:opacity-40">
                            Selanjutnya &rarr;
                        </button>
                    </div>
                </div>
            </div>

            {{-- Question navigator grid --}}
            <div class="pbsf-glass p-4 lg:sticky lg:top-24 lg:h-fit">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-semibold text-[#dfe5f5]">Navigasi Soal</p>
                    <span class="text-xs font-semibold text-pbsf-gold-light" x-text="`${terjawabCount()}/${soal.length} terjawab`"></span>
                </div>
                <div class="mb-4 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-linear-to-r from-pbsf-rain to-[#52c2ae] transition-[width] duration-300" :style="`width: ${(terjawabCount() / soal.length) * 100}%`"></div>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <template x-for="(s, i) in soal" :key="s.soalId">
                        <button type="button" @click="index = i"
                            class="relative flex h-10 items-center justify-center rounded-lg text-xs font-bold transition hover:-translate-y-px"
                            :class="{
                                'ring-2 ring-pbsf-gold ring-offset-2 ring-offset-pbsf-ink': i === index,
                                'bg-pbsf-gold text-pbsf-navy': isRagu(s),
                                'bg-pbsf-rain text-white': !isRagu(s) && s.jawabanSaya,
                                'border border-white/10 bg-white/[.06] text-[#aab4d4] hover:bg-white/10': !isRagu(s) && !s.jawabanSaya,
                            }"
                            :aria-label="`Soal ${i + 1}`"
                            x-text="i + 1"></button>
                    </template>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-[#aab4d4]">
                    <p class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-pbsf-rain"></span> Terjawab</p>
                    <p class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-pbsf-gold"></span> Ragu-ragu</p>
                    <p class="flex items-center gap-1.5"><span class="size-2.5 rounded-full border border-white/20 bg-white/10"></span> Belum dijawab</p>
                    <p class="flex items-center gap-1.5"><span class="size-2.5 rounded-full ring-2 ring-pbsf-gold"></span> Soal aktif</p>
                </div>
            </div>
        </div>

        {{-- Themed "Selesaikan" confirmation (replaces the browser's confirm()) --}}
        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="konfirmasi-judul">
            <div x-show="confirmOpen" x-transition.opacity class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="confirmOpen = false"></div>

            <div x-show="confirmOpen" x-transition.scale.95 class="relative w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-pbsf-navy/95 p-6 text-white shadow-[0_30px_80px_rgba(0,0,0,.6)]">
                <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain"></div>

                <h2 id="konfirmasi-judul" class="text-lg font-bold">Selesaikan ujian sekarang?</h2>
                <p class="mt-1 text-sm text-[#aab4d4]">Setelah diselesaikan, jawaban tidak bisa diubah lagi.</p>

                <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl border border-pbsf-rain/35 bg-pbsf-rain/10 p-3">
                        <p class="text-xl font-bold text-[#52c2ae]" x-text="terjawabCount()"></p>
                        <p class="text-[11px] text-[#aab4d4]">Terjawab</p>
                    </div>
                    <div class="rounded-xl border border-pbsf-gold/35 bg-pbsf-gold/10 p-3">
                        <p class="text-xl font-bold text-pbsf-gold-light" x-text="raguCount()"></p>
                        <p class="text-[11px] text-[#aab4d4]">Ragu-ragu</p>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/[.05] p-3">
                        <p class="text-xl font-bold text-[#dfe5f5]" x-text="soal.length - terjawabCount()"></p>
                        <p class="text-[11px] text-[#aab4d4]">Kosong</p>
                    </div>
                </div>

                <p x-show="soal.length - terjawabCount() > 0 || raguCount() > 0" class="mt-4 rounded-lg bg-pbsf-gold/10 px-3 py-2 text-xs text-pbsf-gold-light">
                    Masih ada soal yang kosong atau ditandai ragu-ragu. Yakin mau selesai sekarang?
                </p>
                <p x-show="submitError" x-cloak role="alert" class="mt-4 rounded-lg bg-error-500/15 px-3 py-2 text-xs text-error-200" x-text="submitError"></p>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="confirmOpen = false" class="pbsf-btn-ghost">Periksa Lagi</button>
                    <button type="button" x-ref="konfirmasiSelesai" @click="kirim()" :disabled="submitting" class="pbsf-btn-gold">
                        <span x-text="submitting ? 'Menyelesaikan…' : 'Ya, Selesaikan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function ujianApp({ soal, mulai, deadline, jawabUrl, submitUrl, raguKey }) {
                return {
                    soal,
                    mulai,
                    deadline,
                    jawabUrl,
                    submitUrl,
                    raguKey,
                    index: 0,
                    remaining: 0,
                    submitting: false,
                    submitError: '',
                    confirmOpen: false,
                    // soalIds flagged "ragu-ragu" — a personal reminder only,
                    // kept in this browser (never sent to the server).
                    ragu: [],
                    timer: null,

                    init() {
                        try {
                            const tersimpan = JSON.parse(localStorage.getItem(this.raguKey) || '[]');
                            const ids = this.soal.map((s) => s.soalId);
                            this.ragu = Array.isArray(tersimpan) ? tersimpan.filter((id) => ids.includes(id)) : [];
                        } catch (e) {
                            this.ragu = [];
                        }

                        this.tick();
                        this.timer = setInterval(() => this.tick(), 1000);
                    },

                    tick() {
                        this.remaining = Math.max(0, Math.floor((this.deadline - Date.now()) / 1000));
                        if (this.remaining <= 0) {
                            clearInterval(this.timer);
                            this.kirim();
                        }
                    },

                    formatWaktu() {
                        const h = Math.floor(this.remaining / 3600).toString().padStart(2, '0');
                        const m = Math.floor((this.remaining % 3600) / 60).toString().padStart(2, '0');
                        const s = Math.floor(this.remaining % 60).toString().padStart(2, '0');
                        return `${h}:${m}:${s}`;
                    },

                    // Percentage of the attempt's total allotted time already
                    // elapsed (0 at start, 100 at the deadline) — drives the
                    // visual time bar. Based on `mulai`, not page-load time,
                    // so a refresh mid-exam doesn't reset the bar.
                    //
                    // Deliberately derived from `this.remaining` rather than
                    // calling Date.now() directly: Alpine's :style binding
                    // only re-evaluates when a property it actually reads
                    // changes, and Date.now() isn't one — reading it here
                    // instead of `remaining` left the bar frozen at whatever
                    // width it had on page load until something else forced
                    // a re-render (e.g. a refresh). `remaining` is already
                    // ticked every second by tick(), so tying the bar to it
                    // makes it update live along with the countdown text.
                    progressPercent() {
                        const total = this.deadline - this.mulai;
                        if (total <= 0) return 100;
                        const elapsed = total - (this.remaining * 1000);
                        return Math.min(100, Math.max(0, (elapsed / total) * 100));
                    },

                    terjawabCount() {
                        return this.soal.filter((s) => s.jawabanSaya).length;
                    },

                    raguCount() {
                        return this.ragu.length;
                    },

                    currentSoal() {
                        return this.soal[this.index];
                    },

                    prev() {
                        this.index = Math.max(0, this.index - 1);
                    },

                    next() {
                        this.index = Math.min(this.soal.length - 1, this.index + 1);
                    },

                    isRagu(s) {
                        return this.ragu.includes(s.soalId);
                    },

                    toggleRagu() {
                        const id = this.currentSoal().soalId;
                        this.ragu = this.isRagu(this.currentSoal()) ? this.ragu.filter((r) => r !== id) : [...this.ragu, id];
                        try {
                            localStorage.setItem(this.raguKey, JSON.stringify(this.ragu));
                        } catch (e) { /* storage blocked — flag still works for this page view */ }
                    },

                    // ← → move between soal, A–E pick an answer, R toggles
                    // ragu-ragu. Ignored while typing in a field, with a
                    // modifier held, or while the confirm dialog is open.
                    onKey(e) {
                        if (this.confirmOpen) {
                            if (e.key === 'Escape') this.confirmOpen = false;
                            return;
                        }
                        if (e.ctrlKey || e.metaKey || e.altKey) return;
                        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable) return;

                        if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            this.prev();
                        } else if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            this.next();
                        } else if (e.key.length === 1) {
                            const huruf = e.key.toUpperCase();
                            if (huruf === 'R') {
                                this.toggleRagu();
                            } else if (Object.prototype.hasOwnProperty.call(this.currentSoal().pilihan, huruf)) {
                                this.pilih(huruf);
                            }
                        }
                    },

                    pilih(huruf) {
                        const soalAktif = this.currentSoal();
                        soalAktif.jawabanSaya = huruf;
                        this.simpan(soalAktif);
                    },

                    // Autosave one answer. `seq` guards against out-of-order
                    // responses when a student clicks several options quickly:
                    // only the latest request is allowed to set the status.
                    async simpan(s) {
                        const seq = (s.seq || 0) + 1;
                        s.seq = seq;
                        s.status = 'saving';

                        try {
                            const response = await fetch(this.jawabUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ soal_id: s.soalId, jawaban: s.jawabanSaya }),
                            });

                            if (response.status === 401 || response.status === 419) {
                                throw new Error('Sesi login berakhir — muat ulang halaman lalu login lagi.');
                            }
                            if (!response.ok) {
                                // 422s carry the server's own (Indonesian) reason, e.g. time's up.
                                const data = response.status === 422 ? await response.json().catch(() => ({})) : {};
                                throw new Error(data.message || 'Gagal menyimpan jawaban.');
                            }

                            if (s.seq === seq) s.status = 'saved';
                        } catch (e) {
                            if (s.seq !== seq) return;
                            s.status = 'error';
                            s.errorMessage = e instanceof TypeError ? 'Gagal menyimpan — periksa koneksi.' : e.message;
                        }
                    },

                    submit() {
                        if (this.submitting) return;
                        this.submitError = '';
                        this.confirmOpen = true;
                        this.$nextTick(() => this.$refs.konfirmasiSelesai?.focus());
                    },

                    async kirim() {
                        if (this.submitting) return;
                        this.submitting = true;
                        this.submitError = '';

                        try {
                            const response = await fetch(this.submitUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                            });
                            if (!response.ok) throw new Error();
                            const data = await response.json();
                            try { localStorage.removeItem(this.raguKey); } catch (e) { /* ignore */ }
                            window.location.href = data.redirect;
                        } catch (e) {
                            this.submitting = false;
                            this.submitError = 'Gagal menyelesaikan ujian — periksa koneksi lalu coba lagi.';
                            this.confirmOpen = true;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-layouts.cbt>
