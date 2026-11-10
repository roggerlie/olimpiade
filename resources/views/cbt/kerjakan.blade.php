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

    {{-- The sheet itself is shared with the admin preview
        (admin/bank-soal/preview.blade.php) — see cbt/partials/lembar-ujian. --}}
    @include('cbt.partials.lembar-ujian', [
        'judul' => $pesertaUjian->ujian->nama,
        'subjudul' => $pesertaUjian->ujian->pelajaran->nama ?? '',
        'soal' => $soal,
        'preview' => false,
        'mulai' => $pesertaUjian->waktu_mulai,
        'deadline' => $batasWaktu,
        'jawabUrl' => route('cbt.ujian.jawab', $pesertaUjian),
        'submitUrl' => route('cbt.ujian.submit', $pesertaUjian),
        'raguKey' => 'pbsf-ragu-'.$pesertaUjian->id,
    ])
</x-layouts.cbt>
