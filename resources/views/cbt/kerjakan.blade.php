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
        })"
        x-init="init()"
        class="mx-auto max-w-5xl"
    >
        {{-- Exam name + live countdown + submit button. Gradient flips from
            brand to error in the last 5 minutes — the whole card becomes the
            "time's almost up" cue, not just the numbers inside it. --}}
        <div class="relative mb-4 overflow-hidden rounded-2xl bg-gradient-to-br p-4 text-white shadow-theme-lg transition-colors duration-700"
            :class="remaining <= 300 ? 'from-error-500 to-error-700' : 'from-brand-500 to-brand-700'">
            <div class="pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-10 right-20 h-20 w-20 rounded-full bg-white/10"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M11.665 3.75621C11.8762 3.65064 12.1247 3.65064 12.3358 3.75621L18.7807 6.97856L12.3358 10.2009C12.1247 10.3065 11.8762 10.3065 11.665 10.2009L5.22014 6.97856L11.665 3.75621ZM4.29297 8.19203V16.0946C4.29297 16.3787 4.45347 16.6384 4.70757 16.7654L11.25 20.0366V11.6513C11.1631 11.6205 11.0777 11.5843 10.9942 11.5426L4.29297 8.19203ZM12.75 20.037L19.2933 16.7654C19.5474 16.6384 19.7079 16.3787 19.7079 16.0946V8.19202L13.0066 11.5426C12.9229 11.5844 12.8372 11.6208 12.75 11.6516V20.037ZM13.0066 2.41456C12.3732 2.09786 11.6277 2.09786 10.9942 2.41456L4.03676 5.89319C3.27449 6.27432 2.79297 7.05342 2.79297 7.90566V16.0946C2.79297 16.9469 3.27448 17.726 4.03676 18.1071L10.9942 21.5857L11.3296 20.9149L10.9942 21.5857C11.6277 21.9024 12.3732 21.9024 13.0066 21.5857L19.9641 18.1071C20.7264 17.726 21.2079 16.9469 21.2079 16.0946V7.90566C21.2079 7.05342 20.7264 6.27432 19.9641 5.89319L13.0066 2.41456Z" fill="currentColor" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold">{{ $pesertaUjian->ujian->nama }}</p>
                        <p class="text-xs text-white/70">{{ $pesertaUjian->ujian->pelajaran->nama ?? '' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-white/70">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 3.25C7.16751 3.25 3.25 7.16751 3.25 12C3.25 16.8325 7.16751 20.75 12 20.75C16.8325 20.75 20.75 16.8325 20.75 12C20.75 7.16751 16.8325 3.25 12 3.25ZM1.75 12C1.75 6.33908 6.33908 1.75 12 1.75C17.6609 1.75 22.25 6.33908 22.25 12C22.25 17.6609 17.6609 22.25 12 22.25C6.33908 22.25 1.75 17.6609 1.75 12ZM12 6.25C12.4142 6.25 12.75 6.58579 12.75 7V11.6893L15.5303 14.4697C15.8232 14.7626 15.8232 15.2374 15.5303 15.5303C15.2374 15.8232 14.7626 15.8232 14.4697 15.5303L11.4697 12.5303C11.329 12.3896 11.25 12.1989 11.25 12V7C11.25 6.58579 11.5858 6.25 12 6.25Z" fill="currentColor" />
                        </svg>
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-white/60">Sisa Waktu</p>
                            <p class="font-mono text-base font-bold leading-none" x-text="formatWaktu()"></p>
                        </div>
                    </div>

                    <button @click="submit()" :disabled="submitting"
                        class="rounded-lg bg-white px-5 py-2.5 text-sm font-medium shadow-theme-xs hover:bg-gray-50 disabled:opacity-50"
                        :class="remaining <= 300 ? 'text-error-600' : 'text-brand-600'">
                        Selesaikan
                    </button>
                </div>
            </div>

            {{-- Visual time-remaining bar: drains left-to-right. --}}
            <div class="relative mt-3 h-1.5 w-full overflow-hidden rounded-full bg-white/20">
                <div class="h-full rounded-full bg-white transition-[width] duration-1000 ease-linear"
                    :style="`width: ${progressPercent()}%`"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_260px]">
            {{-- Question card — the top accent bar just repeats the header's
                brand color so this card doesn't read as a plain flat box. --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="h-1.5 bg-gradient-to-r from-brand-500 to-brand-700"></div>
                <div class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Soal <span x-text="index + 1"></span> dari <span x-text="soal.length"></span>
                    </p>
                    <span x-show="saving" class="text-xs text-gray-400">Menyimpan...</span>
                    <span x-show="!saving && currentSoal().jawabanSaya" class="flex items-center gap-1 text-xs text-success-500">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.7071 5.29289C21.0976 5.68342 21.0976 6.31658 20.7071 6.70711L9.70711 17.7071C9.31658 18.0976 8.68342 18.0976 8.29289 17.7071L3.29289 12.7071C2.90237 12.3166 2.90237 11.6834 3.29289 11.2929C3.68342 10.9024 4.31658 10.9024 4.70711 11.2929L9 15.5858L19.2929 5.29289C19.6834 4.90237 20.3166 4.90237 20.7071 5.29289Z" fill="currentColor" />
                        </svg>
                        Tersimpan
                    </span>
                </div>

                {{-- Keying this block on `index` forces Alpine to tear it down
                    and remount it whenever the question changes, which is
                    what makes the enter transition below actually replay per
                    question — a plain reactive x-text update wouldn't. --}}
                <template x-for="_ in [index]" :key="index">
                    <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-1" x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="mb-6 text-base text-gray-800 dark:text-white/90 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_img]:mt-2 [&_img]:max-h-64 [&_img]:rounded-lg [&_img]:border [&_img]:border-gray-200 dark:[&_img]:border-gray-700"
                            x-html="currentSoal().pertanyaan"></div>

                        <div class="space-y-3">
                            <template x-for="(html, huruf) in currentSoal().pilihan" :key="huruf">
                                <button type="button" @click="pilih(huruf)"
                                    class="flex w-full items-start gap-3 rounded-xl border p-4 text-left text-sm transition hover:-translate-y-px"
                                    :class="currentSoal().jawabanSaya === huruf
                                        ? 'border-brand-500 bg-brand-50 text-brand-700 shadow-theme-xs dark:bg-brand-500/15 dark:text-brand-400'
                                        : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5'">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold transition"
                                        :class="currentSoal().jawabanSaya === huruf ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 dark:border-gray-700'"
                                        x-text="huruf"></span>
                                    <span class="flex-1 [&_p]:mb-1 [&_p:last-child]:mb-0 [&_img]:mt-1 [&_img]:max-h-32 [&_img]:rounded-lg [&_img]:border [&_img]:border-gray-200 dark:[&_img]:border-gray-700"
                                        x-html="html"></span>
                                    <template x-if="currentSoal().jawabanSaya === huruf">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" x-transition.scale width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.7071 5.29289C21.0976 5.68342 21.0976 6.31658 20.7071 6.70711L9.70711 17.7071C9.31658 18.0976 8.68342 18.0976 8.29289 17.7071L3.29289 12.7071C2.90237 12.3166 2.90237 11.6834 3.29289 11.2929C3.68342 10.9024 4.31658 10.9024 4.70711 11.2929L9 15.5858L19.2929 5.29289C19.6834 4.90237 20.3166 4.90237 20.7071 5.29289Z" fill="currentColor" />
                                        </svg>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="mt-6 flex justify-between">
                    <button type="button" @click="index = Math.max(0, index - 1)" :disabled="index === 0"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        &larr; Sebelumnya
                    </button>
                    <button type="button" @click="index = Math.min(soal.length - 1, index + 1)" :disabled="index === soal.length - 1"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 disabled:opacity-40 disabled:hover:bg-transparent dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                        Selanjutnya &rarr;
                    </button>
                </div>
                </div>
            </div>

            {{-- Question navigator grid --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] lg:h-fit">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Navigasi Soal</p>
                    <span class="text-xs font-medium text-gray-400" x-text="`${terjawabCount()}/${soal.length}`"></span>
                </div>
                <div class="mb-4 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                    <div class="h-full rounded-full bg-success-500 transition-[width] duration-300" :style="`width: ${(terjawabCount() / soal.length) * 100}%`"></div>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <template x-for="(s, i) in soal" :key="s.soalId">
                        <button type="button" @click="index = i"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-xs font-semibold transition hover:-translate-y-px"
                            :class="{
                                'ring-2 ring-brand-500': i === index,
                                'bg-success-500 text-white': s.jawabanSaya && i !== index,
                                'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300': !s.jawabanSaya && i !== index,
                                'bg-brand-500 text-white': i === index,
                            }"
                            x-text="i + 1"></button>
                    </template>
                </div>

                <div class="mt-4 space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <p><span class="mr-1.5 inline-block h-2.5 w-2.5 rounded-full bg-success-500"></span> Terjawab</p>
                    <p><span class="mr-1.5 inline-block h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-white/10"></span> Belum dijawab</p>
                </div>
            </div>
        </div>
    </div>

    <x-cbt.sponsor-strip />

    @push('scripts')
        <script>
            function ujianApp({ soal, mulai, deadline, jawabUrl, submitUrl }) {
                return {
                    soal,
                    mulai,
                    deadline,
                    jawabUrl,
                    submitUrl,
                    index: 0,
                    remaining: 0,
                    saving: false,
                    submitting: false,
                    timer: null,

                    init() {
                        this.tick();
                        this.timer = setInterval(() => this.tick(), 1000);
                    },

                    tick() {
                        this.remaining = Math.max(0, Math.floor((this.deadline - Date.now()) / 1000));
                        if (this.remaining <= 0) {
                            clearInterval(this.timer);
                            this.submit();
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

                    currentSoal() {
                        return this.soal[this.index];
                    },

                    async pilih(huruf) {
                        const soalAktif = this.currentSoal();
                        soalAktif.jawabanSaya = huruf;
                        this.saving = true;

                        try {
                            await fetch(this.jawabUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ soal_id: soalAktif.soalId, jawaban: huruf }),
                            });
                        } finally {
                            this.saving = false;
                        }
                    },

                    async submit() {
                        if (this.submitting) return;
                        if (this.remaining > 0 && !confirm('Selesaikan jawabanmu sekarang? Setelah diselesaikan, jawaban tidak bisa diubah lagi.')) {
                            return;
                        }

                        this.submitting = true;
                        const response = await fetch(this.submitUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const data = await response.json();
                        window.location.href = data.redirect;
                    },
                };
            }
        </script>
    @endpush
</x-layouts.cbt>
