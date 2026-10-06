<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Peserta</title>

    @vite(['resources/css/app.css'])

    <style>
        /* Keep the navy/gold fills when printing (browsers drop backgrounds by default). */
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        @page { size: A4 portrait; margin: 10mm; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .lembar { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: auto !important; min-height: 0 !important; }
        }

        /* Explicit sheets of 8 (2 x 4) — predictable page breaks, never a card split across pages. */
        .lembar { break-after: page; }
        .lembar:last-child { break-after: auto; }

        /* Crop marks just outside each card's corners. */
        .slot { position: relative; }
        .slot > .tanda { position: absolute; width: 2.5mm; height: 2.5mm; border: 0 solid #9ca3af; }
        .slot > .tanda.tl { top: -1.5mm; left: -1.5mm; border-top-width: .2mm; border-left-width: .2mm; }
        .slot > .tanda.tr { top: -1.5mm; right: -1.5mm; border-top-width: .2mm; border-right-width: .2mm; }
        .slot > .tanda.bl { bottom: -1.5mm; left: -1.5mm; border-bottom-width: .2mm; border-left-width: .2mm; }
        .slot > .tanda.br { bottom: -1.5mm; right: -1.5mm; border-bottom-width: .2mm; border-right-width: .2mm; }
    </style>
</head>

<body class="bg-gray-200 p-6">
    <div class="no-print mx-auto mb-6 flex max-w-[210mm] items-center justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-gray-700">{{ $jumlah }} kartu peserta siap dicetak.</p>
            <p class="text-xs text-gray-500">Kertas A4, skala 100% (jangan "fit to page"), aktifkan "Background graphics". Potong mengikuti tanda sudut.</p>
        </div>
        <button onclick="window.print()" class="shrink-0 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            Cetak
        </button>
    </div>

    @if ($jumlah === 0)
        <p class="mx-auto max-w-[210mm] text-sm text-gray-500">Tidak ada peserta untuk dicetak.</p>
    @else
        @foreach ($kelompok as $sekolah => $pesertaKelompok)
            @php $halaman = $pesertaKelompok->chunk(8); @endphp
            @foreach ($halaman as $isiHalaman)
                <section class="lembar mx-auto mb-6 w-[210mm] bg-white p-[10mm] shadow-theme-md">
                    @if ($sekolah !== '')
                        <p class="mb-[3mm] flex justify-between border-b-[0.3mm] border-gray-300 pb-[1mm] text-[8pt] text-gray-600">
                            <span class="font-bold text-gray-800">{{ $sekolah }}</span>
                            <span>{{ $pesertaKelompok->count() }} kartu · lembar {{ $loop->iteration }}/{{ $halaman->count() }}</span>
                        </p>
                    @endif

                    <div class="grid grid-cols-[89mm_89mm] justify-center gap-x-[6mm] gap-y-[5mm]">
                        @foreach ($isiHalaman as $s)
                            <div class="slot">
                                <span class="tanda tl"></span><span class="tanda tr"></span><span class="tanda bl"></span><span class="tanda br"></span>
                                <x-kartu-peserta
                                    :nama="$s->nama"
                                    :asal-sekolah="$s->asal_sekolah"
                                    :jenjang="$s->jenjang->nama"
                                    :noreg="$s->noreg"
                                    :password="$s->password_plain"
                                    :jadwal="$s->jadwalKartu()" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endforeach
    @endif
</body>

</html>
