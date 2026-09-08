<x-layouts.admin title="Ubah Soal" :page-title="'Ubah Soal — '.$bankSoal->nama">
    @push('head')
        @vite(['resources/js/soal-editor.js'])
    @endpush

    <div class="mb-4">
        <a href="{{ route('admin.bank-soal.soal', $bankSoal) }}" class="text-sm text-brand-500 hover:text-brand-600">&larr; Kembali ke Daftar Soal</a>
    </div>

    <livewire:admin.soal.form :bank-soal-id="$bankSoal->id" :soal="$soal" :key="'soal-form-'.$soal->id" />
</x-layouts.admin>
