<?php

use App\Models\Pengaturan;
use App\Support\LogAktivitas;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pengaturan → Kartu Peserta: the signature block printed on every kartu
 * peserta — the Ketua Pelaksana's name and their QR (a ready-made image,
 * not generated here). Administrator only, like the rest of Pengaturan.
 */
new class extends Component
{
    use WithFileUploads;

    private const FOLDER_QR = 'pengaturan';

    public string $ketuaPelaksana = '';

    public $qrBaru = null;

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->ensureAdministrator();

        $this->ketuaPelaksana = Pengaturan::ambil(Pengaturan::KETUA_PELAKSANA, '');
    }

    public function simpan(): void
    {
        $this->ensureAdministrator();

        $this->validate([
            'ketuaPelaksana' => ['nullable', 'string', 'max:255'],
            'qrBaru' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], attributes: [
            'ketuaPelaksana' => 'nama ketua pelaksana',
            'qrBaru' => 'gambar QR',
        ]);

        $ketuaLama = Pengaturan::ambil(Pengaturan::KETUA_PELAKSANA);
        Pengaturan::simpan(Pengaturan::KETUA_PELAKSANA, $this->ketuaPelaksana);

        LogAktivitas::admin('ubah_pengaturan', 'Pengaturan kartu peserta diubah', properti: [
            'old' => ['ketua_pelaksana' => $ketuaLama],
            'attributes' => ['ketua_pelaksana' => Pengaturan::ambil(Pengaturan::KETUA_PELAKSANA)],
            'qr_diganti' => (bool) $this->qrBaru,
        ]);

        if ($this->qrBaru) {
            $this->hapusQrUnggahan();
            Pengaturan::simpan(Pengaturan::QR_KETUA, $this->qrBaru->store(self::FOLDER_QR, 'public'));
            $this->qrBaru = null;
        }

        $this->statusMessage = 'Pengaturan kartu peserta disimpan.';
    }

    /**
     * Drops an uploaded QR, so the kartu goes back to the shipped default
     * (Pengaturan::QR_KETUA_BAWAAN).
     */
    public function pakaiQrBawaan(): void
    {
        $this->ensureAdministrator();

        $this->hapusQrUnggahan();
        Pengaturan::simpan(Pengaturan::QR_KETUA, null);
        LogAktivitas::admin('ubah_pengaturan', 'QR ketua pelaksana dikembalikan ke bawaan');

        $this->statusMessage = 'QR dikembalikan ke bawaan.';
    }

    private function hapusQrUnggahan(): void
    {
        if ($lama = Pengaturan::ambil(Pengaturan::QR_KETUA)) {
            Storage::disk('public')->delete($lama);
        }
    }

    private function ensureAdministrator(): void
    {
        abort_unless(auth()->user()?->hasRole('administrator'), 403);
    }
};
