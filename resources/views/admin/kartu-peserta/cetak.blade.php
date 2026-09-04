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

        .kartu {
            width: 85mm;
            height: 54mm;
            page-break-inside: avoid;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">
    <div class="no-print mb-6 flex items-center justify-between">
        <p class="text-sm text-gray-600">{{ $siswa->count() }} kartu peserta siap dicetak.</p>
        <button onclick="window.print()" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
            Cetak
        </button>
    </div>

    @if ($siswa->isEmpty())
        <p class="text-sm text-gray-500">Tidak ada peserta untuk dicetak.</p>
    @else
        <div class="grid grid-cols-2 gap-4">
            @foreach ($siswa as $s)
                <div class="kartu flex flex-col justify-between rounded-xl border-2 border-brand-500 bg-white p-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-brand-500">Kartu Peserta Olimpiade</p>
                        <p class="mt-1 text-sm font-bold text-gray-800">{{ $s->nama }}</p>
                        <p class="text-xs text-gray-500">{{ $s->jenjang->nama }} · {{ $s->asal_sekolah }}</p>
                    </div>

                    <div class="mt-2 rounded-lg bg-gray-50 px-3 py-2">
                        <p class="text-[10px] uppercase text-gray-400">No. Registrasi (Username Login)</p>
                        <p class="font-mono text-lg font-bold tracking-widest text-gray-800">{{ $s->noreg }}</p>
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
