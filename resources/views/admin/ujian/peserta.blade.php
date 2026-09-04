<x-layouts.admin :title="'Peserta — '.$ujian->nama" :page-title="'Peserta: '.$ujian->nama">
    <div class="mb-4">
        <a href="{{ route('admin.ujian.index') }}" class="text-sm text-brand-500 hover:text-brand-600">&larr; Kembali ke Ujian</a>
    </div>

    <livewire:admin.siswa-ujian.manager :ujian-id="$ujian->id" :key="'siswa-ujian-manager-'.$ujian->id" />
</x-layouts.admin>
