<x-layouts.admin :title="'Soal — '.$bankSoal->nama" :page-title="'Soal: '.$bankSoal->nama">
    {{-- No soal-editor.js push here anymore — Tambah/Ubah Soal (the only
        place TinyMCE renders) live on their own pages now; see
        admin/bank-soal/soal-create.blade.php and soal-edit.blade.php.
        This page's own "Lihat Pilihan" preview (admin.soal.manager) still
        renders raw saved soal HTML though, which can contain a WIRIS
        MathType formula — see cbt/kerjakan.blade.php's identical push for
        why this script is here. --}}
    @push('head')
        <script src="https://www.wiris.net/demo/plugins/app/WIRISplugins.js?viewer=image"></script>
    @endpush
    <div class="mb-4">
        <a href="{{ route('admin.bank-soal.index') }}" class="text-sm text-brand-500 hover:text-brand-600">&larr; Kembali ke Bank Soal</a>
    </div>

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.badge color="light">{{ $bankSoal->jenjang->nama }}</x-ui.badge>
            <x-ui.badge color="light">{{ $bankSoal->pelajaran->nama }}</x-ui.badge>
        </div>
        @if ($bankSoal->deskripsi)
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $bankSoal->deskripsi }}</p>
        @endif
    </div>

    <livewire:admin.soal.manager :bank-soal-id="$bankSoal->id" :key="'soal-manager-'.$bankSoal->id" />
</x-layouts.admin>
