@php
    // A small, stable set of Gold / Biru / Hijau Hujan chip colors cycled by
    // pelajaran id, so the same subject always reads the same color across
    // cards without needing a dedicated "warna" column on Pelajaran.
    $warnaMapel = fn (int $pelajaranId) => [
        ['chip' => 'border-pbsf-gold/30 bg-pbsf-gold/10 text-pbsf-gold-light', 'bar' => 'bg-pbsf-gold'],
        ['chip' => 'border-pbsf-blue/40 bg-pbsf-blue/15 text-[#93c5fd]', 'bar' => 'bg-pbsf-blue'],
        ['chip' => 'border-pbsf-rain/40 bg-pbsf-rain/15 text-[#86dbcc]', 'bar' => 'bg-pbsf-rain'],
    ][$pelajaranId % 3];

    $jadwal = fn ($ujian) => $ujian->sesi_mulai->translatedFormat('d M Y, H:i').' – '.$ujian->sesi_selesai->translatedFormat('H:i');
@endphp

<x-layouts.cbt title="Dashboard">
    {{-- Hero greeting --}}
    <div class="pbsf-glass relative mb-6 animate-pbsf-fade-up overflow-hidden p-6 motion-reduce:animate-none md:p-8">
        <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-pbsf-gold via-pbsf-blue to-pbsf-rain"></div>
        <div class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-pbsf-gold/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 left-1/3 size-72 rounded-full bg-pbsf-rain/10 blur-3xl"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <x-cbt.avatar :name="$peserta->nama" class="size-16 text-2xl ring-4 ring-pbsf-gold/25" />
                <div>
                    <p class="text-sm text-[#aab4d4]">Selamat datang di arena,</p>
                    <h1 class="text-2xl font-bold md:text-3xl">Halo, <span class="text-pbsf-gold-light">{{ $peserta->nama }}</span></h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-medium">
                        <span class="rounded-full border border-white/15 bg-white/[.06] px-2.5 py-1 text-[#dfe5f5]">No. Registrasi {{ $peserta->noreg }}</span>
                        <span class="rounded-full border border-pbsf-rain/40 bg-pbsf-rain/15 px-2.5 py-1 text-[#86dbcc]">{{ $peserta->jenjang->nama }}</span>
                    </div>
                </div>
            </div>

            <img src="/images/pbsf/logo-pbsf-3d.webp" alt="" width="816" height="382"
                class="hidden w-64 animate-pbsf-float drop-shadow-[0_14px_30px_rgba(0,0,0,.5)] motion-reduce:animate-none md:block lg:w-72" />
        </div>
    </div>

    @if (session('error'))
        <div role="alert" class="mb-6 rounded-xl border border-error-500/40 bg-error-500/10 px-4 py-3 text-sm text-error-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- Priority card: the one thing most worth acting on right now --}}
    @if ($prioritas)
        <div class="relative mb-6 animate-pbsf-fade-up rounded-2xl [animation-delay:.08s] motion-reduce:animate-none">
            <div class="pbsf-glass animate-pbsf-glow border-pbsf-gold/40 bg-linear-to-br from-pbsf-gold/[.12] to-white/[.03] p-5 motion-reduce:animate-none md:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-pbsf-gold-light to-pbsf-gold-deep text-pbsf-navy shadow-lg shadow-pbsf-gold/20">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold tracking-[.12em] text-pbsf-gold uppercase">
                                {{ $prioritas->waktu_mulai ? 'Sedang kamu kerjakan' : 'Perlu perhatianmu' }}
                            </p>
                            <p class="mt-1 text-lg font-bold">{{ $prioritas->ujian->nama }}</p>
                            <p class="text-sm text-[#aab4d4]">{{ $jadwal($prioritas->ujian) }}</p>
                        </div>
                    </div>

                    @if ($prioritas->waktu_mulai)
                        <a href="{{ route('cbt.ujian.kerjakan', $prioritas) }}" class="pbsf-btn-gold px-6">Lanjutkan &rarr;</a>
                    @elseif ($prioritas->ujian->sesiSedangBerlangsung())
                        <a href="{{ route('cbt.ujian.petunjuk', $prioritas) }}" class="pbsf-btn-gold px-6">Mulai Ujian &rarr;</a>
                    @else
                        <span class="rounded-full border border-white/15 bg-white/[.06] px-3 py-1.5 text-xs font-semibold text-[#aab4d4]">Belum Dibuka</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Summary stat cards --}}
    @php
        $statCards = [
            ['label' => 'Total Ujian', 'value' => $statistik['total'], 'icon' => 'from-pbsf-blue to-[#1d4ed8] text-white', 'path' => '<rect x="4" y="5" width="16" height="16" rx="2" /><path d="M16 3v4M8 3v4M4 11h16" />'],
            ['label' => 'Selesai', 'value' => $statistik['selesai'], 'icon' => 'from-pbsf-rain to-pbsf-rain-deep text-white', 'path' => '<path d="m5 12 5 5L20 7" />'],
            ['label' => 'Belum Selesai', 'value' => $statistik['belumSelesai'], 'icon' => 'from-pbsf-gold-light to-pbsf-gold-deep text-pbsf-navy', 'path' => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" />'],
        ];
    @endphp
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($statCards as $card)
            <div class="pbsf-glass flex animate-pbsf-fade-up items-center gap-4 p-5 motion-reduce:animate-none" style="animation-delay: {{ 0.12 + $loop->index * 0.06 }}s">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br shadow-lg {{ $card['icon'] }}">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $card['path'] !!}</svg>
                </div>
                <div>
                    <p class="text-sm text-[#aab4d4]">{{ $card['label'] }}</p>
                    <p class="text-3xl leading-tight font-bold">{{ $card['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- The priority card above already covers one ujian — no need to repeat it here. --}}
    @php $daftarUjian = $prioritas ? $pesertaUjian->reject(fn ($su) => $su->id === $prioritas->id) : $pesertaUjian; @endphp

    @if ($pesertaUjian->isEmpty() || $daftarUjian->isNotEmpty())
        <div class="mb-4 flex items-center gap-3 text-[13px] font-semibold tracking-[.14em] text-[#8e9acb] uppercase">
            Daftar Ujian
            <span class="h-px flex-1 bg-linear-to-r from-white/15 to-transparent"></span>
        </div>
    @endif

    @if ($pesertaUjian->isEmpty())
        <div class="pbsf-glass flex flex-col items-center p-10 text-center">
            <div class="mb-4 flex size-14 items-center justify-center rounded-full bg-white/[.06] text-[#8e9acb]">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="5" width="16" height="16" rx="2" /><path d="M16 3v4M8 3v4M4 11h16" />
                </svg>
            </div>
            <p class="font-semibold">Belum ada ujian yang terdaftar</p>
            <p class="mt-1 text-sm text-[#aab4d4]">Hubungi panitia jika kamu merasa ini keliru.</p>
        </div>
    @elseif ($daftarUjian->isNotEmpty())
        <div class="space-y-3">
            @foreach ($daftarUjian as $su)
                @php $warna = $warnaMapel($su->ujian->pelajaran_id); @endphp
                {{-- fade-up lives on a wrapper: its fill-mode would otherwise pin transform and cancel the hover lift --}}
                <div class="animate-pbsf-fade-up motion-reduce:animate-none" style="animation-delay: {{ 0.2 + $loop->index * 0.05 }}s">
                <div class="pbsf-glass group relative overflow-hidden p-5 transition duration-300 hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/[.07]">
                    <div class="absolute inset-y-0 left-0 w-1 {{ $warna['bar'] }}"></div>

                    <div class="flex flex-wrap items-center justify-between gap-4 pl-2">
                        <div>
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <p class="font-bold">{{ $su->ujian->nama }}</p>
                                <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $warna['chip'] }}">{{ $su->ujian->pelajaran->nama }}</span>
                            </div>
                            <p class="text-sm text-[#aab4d4]">{{ $jadwal($su->ujian) }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($su->sudahSubmit())
                                <span class="rounded-full border border-pbsf-rain/40 bg-pbsf-rain/15 px-3 py-1.5 text-xs font-semibold text-[#86dbcc]">Selesai &middot; Nilai {{ $su->nilai }}</span>
                                <a href="{{ route('cbt.ujian.hasil', $su) }}" class="pbsf-btn-ghost">Lihat Hasil</a>
                            @elseif ($su->waktu_mulai)
                                <span class="rounded-full border border-pbsf-gold/40 bg-pbsf-gold/10 px-3 py-1.5 text-xs font-semibold text-pbsf-gold-light">Sedang Berlangsung</span>
                                <a href="{{ route('cbt.ujian.kerjakan', $su) }}" class="pbsf-btn-gold">Lanjutkan</a>
                            @elseif (! $su->ujian->sesiSedangBerlangsung())
                                <span class="rounded-full border border-white/15 bg-white/[.06] px-3 py-1.5 text-xs font-semibold text-[#aab4d4]">
                                    {{ now()->lt($su->ujian->sesi_mulai) ? 'Belum Dibuka' : 'Sesi Ditutup' }}
                                </span>
                            @else
                                <a href="{{ route('cbt.ujian.petunjuk', $su) }}" class="pbsf-btn-gold">Mulai Ujian</a>
                            @endif
                        </div>
                    </div>
                </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-cbt.sponsor-strip />
</x-layouts.cbt>
