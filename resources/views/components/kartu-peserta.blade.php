{{--
    One kartu peserta, 89 x 62 mm (8 per A4 — see admin/kartu-peserta/cetak).
    Data-only props so the Kartu Peserta page can render a preview with
    sample data. Sizes are in mm/pt so print output doesn't depend on the
    screen. Colors: PBSF navy + gold, same palette as the peserta login page.
    Kept deliberately sparse — nama, akun login, jadwal — with room to breathe.

    `jadwal`: list of ['lomba', 'tanggal', 'jam', 'ruangan'] — one row per
    registered ujian (ruangan is per ujian, see PesertaUjian::ruangan()).
--}}
@props([
    'nama',
    'asalSekolah',
    'jenjang',
    'noreg',
    'password' => null,
    'jadwal' => [],
])

@php
    $maksJadwal = 3;
    $jadwal = collect($jadwal);
@endphp

<div {{ $attributes->merge(['class' => 'kartu-peserta flex h-[62mm] w-[89mm] flex-col overflow-hidden rounded-[2.5mm] bg-white text-pbsf-ink ring-[0.25mm] ring-gray-300']) }}>
    {{-- Header --}}
    <div class="flex h-[10mm] shrink-0 items-center gap-[2.5mm] border-b-[0.6mm] border-pbsf-gold bg-pbsf-navy px-[4mm]">
        <img src="{{ asset('images/pbsf/logo-pbsf-3d.webp') }}" alt="Panca Budi School Fest Vol. 04" class="h-[7mm] w-auto shrink-0">
        <p class="flex-1 text-[8.5pt] font-extrabold tracking-[0.16em] text-white">KARTU PESERTA</p>
        <span class="shrink-0 rounded-[1mm] bg-pbsf-gold px-[2mm] py-[0.7mm] text-[7pt] font-extrabold uppercase leading-none text-pbsf-navy">{{ $jenjang }}</span>
    </div>

    <div class="flex flex-1 flex-col px-[4mm] pt-[3mm]">
        {{-- Identitas --}}
        <p class="line-clamp-2 text-[11pt] font-extrabold uppercase leading-tight">{{ $nama }}</p>
        <p class="mt-[0.5mm] truncate text-[7pt] text-gray-500">{{ $asalSekolah }}</p>

        {{-- Akun login --}}
        <div class="mt-[3mm] grid grid-cols-2 gap-[2.5mm]">
            <div class="rounded-[1.5mm] bg-pbsf-gold/10 px-[2.5mm] py-[1.5mm]">
                <p class="text-[5.5pt] font-bold tracking-[0.14em] text-pbsf-gold-deep">USERNAME</p>
                <p class="mt-[0.3mm] font-mono text-[10pt] font-bold leading-none tracking-[0.04em]">{{ $noreg }}</p>
            </div>
            <div class="rounded-[1.5mm] bg-pbsf-gold/10 px-[2.5mm] py-[1.5mm]">
                <p class="text-[5.5pt] font-bold tracking-[0.14em] text-pbsf-gold-deep">PASSWORD</p>
                <p class="mt-[0.3mm] font-mono text-[10pt] font-bold leading-none tracking-[0.2em]">{{ $password ?? '—' }}</p>
            </div>
        </div>

        {{-- Jadwal --}}
        <p class="mt-[3mm] text-[5.5pt] font-bold tracking-[0.14em] text-pbsf-gold-deep">JADWAL UJIAN</p>
        <div class="mt-[1.2mm] space-y-[1.5mm] text-[7.5pt] leading-none">
            @forelse ($jadwal->take($maksJadwal) as $baris)
                <div class="flex items-baseline justify-between gap-[2mm]">
                    <span class="truncate font-bold text-pbsf-navy">{{ $baris['lomba'] }}</span>
                    <span class="shrink-0 text-gray-600">
                        {{ $baris['tanggal'] }} · {{ $baris['jam'] }} ·
                        @if ($baris['ruangan'])
                            <span class="font-semibold text-pbsf-ink">{{ $baris['ruangan'] }}</span>
                        @else
                            <span class="italic text-gray-400">ruangan menyusul</span>
                        @endif
                    </span>
                </div>
            @empty
                <p class="text-gray-400">Belum terdaftar di ujian.</p>
            @endforelse

            @if ($jadwal->count() > $maksJadwal)
                <p class="text-[6pt] text-gray-400">+{{ $jadwal->count() - $maksJadwal }} lomba lainnya</p>
            @endif
        </div>

        {{-- Petunjuk --}}
        <p class="mt-auto pb-[2mm] text-center text-[5.5pt] text-gray-400">Bawa kartu ini saat ujian · Jangan bagikan password</p>
    </div>
</div>
