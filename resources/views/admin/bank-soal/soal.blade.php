<x-layouts.admin :title="'Soal — '.$bankSoal->nama" :page-title="'Soal: '.$bankSoal->nama">
    <div class="mb-4">
        <a href="{{ route('admin.bank-soal.index') }}" class="text-sm text-brand-500 hover:text-brand-600">&larr; Kembali ke Bank Soal</a>
    </div>

    <livewire:admin.soal.manager :bank-soal-id="$bankSoal->id" :key="'soal-manager-'.$bankSoal->id" />
</x-layouts.admin>
