<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Hadir — {{ $ujian->nama }}</title>

    @vite(['resources/css/app.css'])

    <style>
        @media print {
            .no-print { display: none !important; }
            @page { size: A4 portrait; margin: 15mm; }
            body { background: #fff !important; padding: 0 !important; }
            .lembar { box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
        }

        /* One ruangan per printed page. */
        .lembar + .lembar { page-break-before: always; }
        .tabel-hadir th, .tabel-hadir td { border: 1px solid #9ca3af; padding: 6px 8px; }
        .tabel-hadir tbody tr { height: 34px; page-break-inside: avoid; }
    </style>
</head>

<body class="bg-gray-100 p-6 text-gray-900">
    <div class="no-print mx-auto mb-6 flex max-w-[210mm] items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-700">{{ $perRuangan->count() }} ruangan siap dicetak.</p>
            @if ($tanpaRuangan > 0)
                <p class="text-xs text-error-600">{{ $tanpaRuangan }} peserta terdaftar belum punya ruangan dan tidak ikut tercetak.</p>
            @endif
        </div>
        <button onclick="window.print()" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            Cetak
        </button>
    </div>

    @forelse ($perRuangan as $pesertaUjian)
        @php $ruangan = $pesertaUjian->first()->ruangan; @endphp
        <section class="lembar mx-auto mb-6 max-w-[210mm] bg-white p-10 shadow-theme-xs">
            <header class="mb-5 border-b-2 border-gray-800 pb-3 text-center">
                <h1 class="text-lg font-bold uppercase tracking-wide">Daftar Hadir Peserta</h1>
                <p class="text-sm font-semibold">{{ $ujian->nama }}</p>
            </header>

            <table class="mb-4 text-sm">
                <tr><td class="w-32 pr-2">Jenjang / Pelajaran</td><td>: {{ $ujian->jenjang->nama }} · {{ $ujian->pelajaran->nama }}</td></tr>
                <tr><td class="pr-2">Ruangan</td><td>: <span class="font-semibold">{{ $ruangan->nama }}</span>{{ $ruangan->keterangan ? " ({$ruangan->keterangan})" : '' }}</td></tr>
                <tr><td class="pr-2">Waktu</td><td>: {{ $ujian->sesi_mulai->translatedFormat('l, d F Y, H:i') }} – {{ $ujian->sesi_selesai->translatedFormat('H:i') }}</td></tr>
                <tr><td class="pr-2">Jumlah Peserta</td><td>: {{ $pesertaUjian->count() }}</td></tr>
            </table>

            <table class="tabel-hadir w-full border-collapse text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="w-10">No</th>
                        <th class="w-32">No. Registrasi</th>
                        <th>Nama</th>
                        <th>Asal Sekolah</th>
                        <th class="w-40">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pesertaUjian->values() as $i => $pu)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td class="font-mono">{{ $pu->peserta->noreg }}</td>
                            <td>{{ $pu->peserta->nama }}</td>
                            <td>{{ $pu->peserta->asal_sekolah }}</td>
                            {{-- Alternating left/right signature slots, the usual daftar hadir layout. --}}
                            <td class="{{ $i % 2 === 0 ? 'text-left' : 'text-right' }} align-top text-xs text-gray-500">{{ $i + 1 }}.</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-10 flex justify-end text-sm">
                <div class="w-56 text-center">
                    <p>Pengawas Ruangan,</p>
                    <div class="h-20"></div>
                    <p class="border-t border-gray-800 pt-1">(.......................................)</p>
                </div>
            </div>
        </section>
    @empty
        <p class="mx-auto max-w-[210mm] text-sm text-gray-500">Belum ada peserta yang ditempatkan di ruangan untuk ujian ini.</p>
    @endforelse
</body>

</html>
