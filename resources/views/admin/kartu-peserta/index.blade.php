<x-layouts.admin title="Kartu Peserta" page-title="Kartu Peserta">
    <x-common.component-card title="Cetak Kartu Peserta" desc="Pilih jenjang dan/atau pelajaran lalu cetak kartu untuk semua pesertanya, atau biarkan kosong untuk mencetak semuanya.">
        <form method="GET" action="{{ route('admin.kartu-peserta.cetak') }}" target="_blank" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenjang</label>
                <x-form.select name="jenjang" placeholder="Semua Jenjang" wrapper-class="w-64">
                    @foreach (\App\Models\Jenjang::orderBy('id')->get() as $jenjang)
                        <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Pelajaran</label>
                <x-form.select name="pelajaran" placeholder="Semua Pelajaran" wrapper-class="w-64">
                    @foreach (\App\Models\Pelajaran::orderBy('nama')->get() as $pelajaran)
                        <option value="{{ $pelajaran->id }}">{{ $pelajaran->nama }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <x-ui.button type="submit">Lihat &amp; Cetak</x-ui.button>
        </form>
    </x-common.component-card>
</x-layouts.admin>
