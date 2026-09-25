{{--
    Right-side PBSF branding for the peserta login page. Transparent — it
    sits on components/auth/pbsf-backdrop — and hidden below lg, where
    login-peserta shows a compact logo above the form instead.

    `cabangLomba` = pelajaran names, `jenjang` = jenjang names (both from
    master data). Descriptions/icons aren't stored anywhere, so they're
    matched by keyword on the pelajaran name, with a first-letter icon as
    the fallback; colors cycle through the Gold / Biru / Hijau Hujan palette.
--}}
@props(['cabangLomba' => [], 'jenjang' => []])

@php
    $knownLomba = [
        'matematika' => ['icon' => '∑', 'desc' => 'Logika, aljabar & pemecahan masalah'],
        'sains' => ['icon' => '⚛', 'desc' => 'Fisika, kimia & biologi'],
        'ipa' => ['icon' => '⚛', 'desc' => 'Fisika, kimia & biologi'],
        'inggris' => ['icon' => 'En', 'desc' => 'Reading, grammar & vocabulary'],
    ];
    $iconStyles = [
        'from-pbsf-gold to-pbsf-gold-deep text-pbsf-navy shadow-pbsf-gold/30',
        'from-pbsf-blue to-[#1d4ed8] text-white shadow-pbsf-blue/30',
        'from-pbsf-rain to-pbsf-rain-deep text-white shadow-pbsf-rain/30',
    ];

    $lomba = collect($cabangLomba)->values()->map(function (string $nama, int $i) use ($knownLomba, $iconStyles) {
        $known = collect($knownLomba)->first(fn ($meta, $keyword) => str_contains(strtolower($nama), $keyword));

        return [
            'name' => 'Olimpiade '.$nama,
            'desc' => $known['desc'] ?? null,
            'icon' => $known['icon'] ?? mb_strtoupper(mb_substr($nama, 0, 1)),
            'iconStyle' => $iconStyles[$i % count($iconStyles)],
        ];
    });
@endphp

<div class="relative hidden text-white lg:flex lg:min-h-screen lg:flex-1">
    <div class="mx-auto flex w-full max-w-[640px] flex-col items-center px-10 py-12 xl:px-14 2xl:py-16">
        {{-- Logo --}}
        <a href="{{ url('/') }}" class="relative block w-[380px] animate-pbsf-fade-up xl:w-[440px] 2xl:w-[500px] motion-reduce:animate-none">
            <img src="/images/pbsf/logo-pbsf-3d.webp" alt="Panca Budi School Fest Vol. 04" width="816" height="382"
                class="relative block w-full animate-pbsf-float drop-shadow-[0_18px_40px_rgba(0,0,0,.55)] motion-reduce:animate-none" />
        </a>

        <div class="mt-6 flex animate-pbsf-fade-up items-center gap-2.5 rounded-full border border-pbsf-gold/35 bg-pbsf-gold/10 px-4 py-2 text-[13px] font-semibold tracking-[.08em] text-pbsf-gold-light uppercase [animation-delay:.1s] motion-reduce:animate-none 2xl:text-sm">
            <span class="relative flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-pbsf-rain opacity-75 motion-reduce:hidden"></span>
                <span class="relative inline-flex size-2 rounded-full bg-pbsf-rain"></span>
            </span>
            <span>Perguruan Panca Budi Medan</span>
        </div>

        <p class="mt-4 animate-pbsf-fade-up text-center text-xl font-medium text-[#dfe5f5] italic [animation-delay:.15s] motion-reduce:animate-none xl:text-[22px]">
            “Tempat Dimana Impian Terbentuk”
        </p>

        {{-- Cabang lomba --}}
        @if ($lomba->isNotEmpty())
            <div class="mt-10 flex w-full flex-col gap-3">
                <div class="mb-1 flex animate-pbsf-fade-up items-center gap-3 text-[13px] font-semibold tracking-[.14em] text-[#8e9acb] uppercase [animation-delay:.2s] motion-reduce:animate-none">
                    Cabang Lomba
                    <span class="h-px flex-1 bg-linear-to-r from-white/15 to-transparent"></span>
                </div>

                @foreach ($lomba as $l)
                    <div class="animate-pbsf-fade-up motion-reduce:animate-none" style="animation-delay: {{ 0.25 + $loop->index * 0.08 }}s">
                    <div class="group flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[.05] px-4 py-3.5 backdrop-blur-sm transition duration-300 hover:-translate-y-0.5 hover:border-pbsf-gold/30 hover:bg-white/[.08] 2xl:gap-5 2xl:px-5 2xl:py-4">
                        <div class="flex size-12 flex-none items-center justify-center rounded-xl bg-linear-to-br text-xl font-extrabold shadow-lg transition group-hover:scale-105 2xl:size-14 2xl:text-2xl {{ $l['iconStyle'] }}">
                            {{ $l['icon'] }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-lg font-bold xl:text-xl">{{ $l['name'] }}</div>
                            @if ($l['desc'])
                                <div class="mt-0.5 text-[13px] text-[#aab4d4] 2xl:text-sm">{{ $l['desc'] }}</div>
                            @endif
                        </div>
                        <div class="flex flex-wrap justify-end gap-1.5">
                            @foreach ($jenjang as $j)
                                <span class="rounded-lg border border-white/10 bg-white/[.07] px-2.5 py-1 text-xs font-semibold text-[#e7ecfa]">{{ $j }}</span>
                            @endforeach
                        </div>
                    </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Sponsor --}}
        <div class="mt-auto flex animate-pbsf-fade-up flex-col items-center gap-3 pt-10 [animation-delay:.5s] motion-reduce:animate-none">
            <div class="text-xs font-semibold tracking-[.16em] text-[#8e9acb] uppercase">Didukung oleh</div>
            <div class="flex items-center rounded-[14px] bg-white px-6 py-3 shadow-[0_12px_30px_rgba(0,0,0,.3)]">
                <img src="/images/pbsf/adzkia-kedinasan.webp" alt="Adzkia Kedinasan" class="block h-8 2xl:h-9" />
            </div>
        </div>
    </div>
</div>
