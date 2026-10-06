<x-layouts.admin title="Kartu Peserta" page-title="Kartu Peserta">
    <div class="grid gap-6 xl:grid-cols-[1fr_auto]">
        <x-common.component-card title="Cetak Kartu Peserta" desc="Pilih jenjang dan/atau pelajaran, atau biarkan kosong untuk mencetak semuanya. Hasilnya dibuka di tab baru untuk dicek sebelum dicetak.">
            <form method="GET" action="{{ route('admin.kartu-peserta.cetak') }}" target="_blank" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenjang</label>
                    <x-form.select name="jenjang" placeholder="Semua Jenjang" wrapper-class="w-56">
                        @foreach (\App\Models\Jenjang::orderBy('id')->get() as $jenjang)
                            <option value="{{ $jenjang->id }}">{{ $jenjang->nama }}</option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Pelajaran</label>
                    <x-form.select name="pelajaran" placeholder="Semua Pelajaran" wrapper-class="w-56">
                        @foreach (\App\Models\Pelajaran::orderBy('nama')->get() as $pelajaran)
                            <option value="{{ $pelajaran->id }}">{{ $pelajaran->nama }}</option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Urutan</label>
                    <x-form.select name="urutan" wrapper-class="w-56">
                        <option value="nama">Nama peserta</option>
                        <option value="sekolah">Asal sekolah (per lembar)</option>
                        <option value="noreg">No. Registrasi</option>
                    </x-form.select>
                </div>

                <x-ui.button type="submit">Lihat &amp; Cetak</x-ui.button>
            </form>

            <ul class="mt-5 list-disc space-y-1 pl-5 text-xs text-gray-500 dark:text-gray-400">
                <li>8 kartu per lembar A4. Cetak berwarna, skala 100%, dan aktifkan <em>Background graphics</em>.</li>
                <li>Urutan <strong>asal sekolah</strong> memulai tiap sekolah di lembar baru, lengkap dengan nama sekolahnya, jadi kartu gampang dibagikan per sekolah.</li>            </ul>
        </x-common.component-card>

        <x-common.component-card title="Contoh Kartu" desc="Ukuran asli 89 × 62 mm.">
            <x-kartu-peserta
                nama="Budi Santoso"
                asal-sekolah="SMA Negeri 1 Medan"
                jenjang="SLTA"
                noreg="0123456789"
                password="7F8PV3"
                :jadwal="[
                    ['lomba' => 'IPA', 'tanggal' => 'Sab, 12 Okt', 'jam' => '08.00', 'ruangan' => 'Lab Komputer 1'],
                    ['lomba' => 'MATEMATIKA', 'tanggal' => 'Sab, 12 Okt', 'jam' => '13.00', 'ruangan' => null],
                ]"
                class="mx-auto shadow-theme-md" />
        </x-common.component-card>
    </div>
</x-layouts.admin>
