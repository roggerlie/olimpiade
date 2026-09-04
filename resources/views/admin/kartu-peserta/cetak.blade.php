<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Peserta</title>

    @vite(['resources/css/app.css'])

    <style>
        @media print {
            .no-print { display: none !important; }
            @page { margin: 10mm; }
        }

        /* Standard ID-card size (CR80, ~credit card) */
        .kartu-slot {
            width: 89mm;
            height: 58mm;
            page-break-inside: avoid;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">
    <div class="no-print mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-700">{{ $peserta->count() }} kartu peserta siap dicetak.</p>
            <p class="text-xs text-gray-500">Potong mengikuti garis putus-putus di setiap kartu.</p>
        </div>
        <button onclick="window.print()" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            Cetak
        </button>
    </div>

    @if ($peserta->isEmpty())
        <p class="text-sm text-gray-500">Tidak ada peserta untuk dicetak.</p>
    @else
        <div class="grid grid-cols-2 gap-6">
            @foreach ($peserta as $s)
                {{-- Dashed outer box = cut guide; the actual card sits inside with a little breathing room. --}}
                <div class="kartu-slot flex items-center justify-center rounded-2xl border border-dashed border-gray-300 p-1.5">
                    <div class="flex h-full w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-xs">
                        <div class="flex items-center justify-between bg-brand-500 px-4 py-2">
                            <p class="text-[9px] font-semibold uppercase tracking-widest text-white">Kartu Peserta Olimpiade</p>
                            <span class="rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-bold uppercase text-white">{{ $s->jenjang->nama }}</span>
                        </div>

                        <div class="flex flex-1 flex-col justify-between px-4 py-3">
                            <div>
                                <p class="text-base font-bold leading-tight text-gray-800">{{ $s->nama }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $s->asal_sekolah }}</p>
                            </div>

                            <div class="mt-2 rounded-lg bg-gray-50 px-3 py-2">
                                <p class="text-[9px] uppercase tracking-wide text-gray-400">No. Registrasi (Username Login)</p>
                                <p class="font-mono text-xl font-bold tracking-[0.2em] text-gray-800">{{ $s->noreg }}</p>
                            </div>
                        </div>

                        <p class="border-t border-gray-100 px-4 py-1.5 text-center text-[8px] text-gray-400">
                            Bawa kartu ini saat pelaksanaan ujian olimpiade
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>

</html>
