<x-layouts.cbt :title="'Preview — '.$bankSoal->nama" :preview="true">
    @push('head')
        {{-- Same WIRIS viewer as cbt/kerjakan.blade.php, so MathType formulas
            render here exactly as peserta see them. --}}
        <script src="https://www.wiris.net/demo/plugins/app/WIRISplugins.js?viewer=image"></script>
    @endpush

    @if ($soal->isEmpty())
        <div class="pbsf-glass mx-auto max-w-xl p-8 text-center">
            <p class="font-bold">Bank soal ini belum punya soal.</p>
            <p class="mt-1 text-sm text-[#aab4d4]">Tambahkan soal dulu untuk melihat preview tampilan peserta.</p>
            <a href="{{ route('admin.bank-soal.soal', $bankSoal) }}" class="pbsf-btn-gold mt-5 inline-flex">Kembali ke Bank Soal</a>
        </div>
    @else
        {{-- Same sheet peserta get in cbt/kerjakan.blade.php, in read-only
            preview mode: every soal of the bank in its original order, no
            timer, nothing saved. --}}
        @include('cbt.partials.lembar-ujian', [
            'judul' => $bankSoal->nama,
            'subjudul' => trim(($bankSoal->jenjang->nama ?? '').' · '.($bankSoal->pelajaran->nama ?? ''), ' ·'),
            'soal' => $soal,
            'preview' => true,
            'kembaliUrl' => route('admin.bank-soal.soal', $bankSoal),
        ])
    @endif
</x-layouts.cbt>
