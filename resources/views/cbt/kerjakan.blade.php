<x-layouts.cbt :title="$pesertaUjian->ujian->nama">
    <div
        x-data="ujianApp({
            soal: @js($soal),
            deadline: {{ $batasWaktu->timestamp * 1000 }},
            jawabUrl: '{{ route('cbt.ujian.jawab', $pesertaUjian) }}',
            submitUrl: '{{ route('cbt.ujian.submit', $pesertaUjian) }}',
        })"
        x-init="init()"
        class="mx-auto max-w-5xl"
    >
        {{-- Exam name + live countdown + submit button --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div>
                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $pesertaUjian->ujian->nama }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pesertaUjian->ujian->pelajaran->nama ?? '' }}</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Sisa Waktu</p>
                    <p class="font-mono text-lg font-bold" :class="remaining <= 300 ? 'text-error-500' : 'text-gray-800 dark:text-white/90'" x-text="formatWaktu()"></p>
                </div>

                <button @click="submit()" :disabled="submitting"
                    class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
                    Kumpulkan
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_260px]">
            {{-- Question card --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Soal <span x-text="index + 1"></span> dari <span x-text="soal.length"></span>
                    </p>
                    <span x-show="saving" class="text-xs text-gray-400">Menyimpan...</span>
                    <span x-show="!saving && currentSoal().jawabanSaya" class="text-xs text-success-500">Tersimpan</span>
                </div>

                <p class="mb-6 whitespace-pre-line text-base text-gray-800 dark:text-white/90" x-text="currentSoal().pertanyaan"></p>

                <div class="space-y-3">
                    <template x-for="(teks, huruf) in currentSoal().pilihan" :key="huruf">
                        <button type="button" @click="pilih(huruf)"
                            class="flex w-full items-start gap-3 rounded-xl border p-4 text-left text-sm transition"
                            :class="currentSoal().jawabanSaya === huruf
                                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400'
                                : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5'">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold"
                                :class="currentSoal().jawabanSaya === huruf ? 'border-brand-500 bg-brand-500 text-white' : 'border-gray-300 dark:border-gray-700'"
                                x-text="huruf"></span>
                            <span x-text="teks"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" @click="index = Math.max(0, index - 1)" :disabled="index === 0"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300">
                        &larr; Sebelumnya
                    </button>
                    <button type="button" @click="index = Math.min(soal.length - 1, index + 1)" :disabled="index === soal.length - 1"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 disabled:opacity-40 dark:border-gray-700 dark:text-gray-300">
                        Selanjutnya &rarr;
                    </button>
                </div>
            </div>

            {{-- Question navigator grid --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] lg:h-fit">
                <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Navigasi Soal</p>
                <div class="grid grid-cols-5 gap-2">
                    <template x-for="(s, i) in soal" :key="s.soalId">
                        <button type="button" @click="index = i"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-xs font-semibold"
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

    @push('scripts')
        <script>
            function ujianApp({ soal, deadline, jawabUrl, submitUrl }) {
                return {
                    soal,
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
                        if (this.remaining > 0 && !confirm('Kumpulkan jawabanmu sekarang? Setelah dikumpulkan, jawaban tidak bisa diubah lagi.')) {
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
