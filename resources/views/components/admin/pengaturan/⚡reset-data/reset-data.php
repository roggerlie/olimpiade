<?php

use App\Enums\KategoriReset;
use App\Services\ResetDataService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /** What the admin must type to confirm — deliberately not a yes/no click. */
    public const FRASA = 'HAPUS DATA';

    /** Confirmation attempts (wrong password/frasa included) per 10 minutes. */
    private const MAKS_PERCOBAAN = 3;

    /**
     * Raw KategoriReset values the admin ticked. Public Livewire state, so
     * never trusted as-is: always re-resolved through KategoriReset::lengkapi(),
     * which drops unknown values and re-adds dependencies server-side.
     *
     * @var list<string>
     */
    public array $dipilih = [];

    public bool $showKonfirmasi = false;

    public string $frasa = '';

    public string $password = '';

    /** @var array{backup: string, terhapus: array<string, int>}|null */
    public ?array $hasil = null;

    public function mount(): void
    {
        $this->ensureAdministrator();
    }

    /**
     * @return list<KategoriReset>
     */
    #[Computed]
    public function kategoriTerpilih(): array
    {
        return KategoriReset::lengkapi($this->dipilih);
    }

    /**
     * Pulled in as a dependency rather than ticked by the admin — rendered
     * checked + locked, with which pick caused it.
     *
     * @return array<string, string> value => label of the category that requires it
     */
    #[Computed]
    public function otomatis(): array
    {
        $hasil = [];

        foreach (KategoriReset::lengkapi($this->dipilih) as $k) {
            if (in_array($k->value, $this->dipilih, true)) {
                continue;
            }

            $penyebab = collect(KategoriReset::cases())
                ->first(fn (KategoriReset $c) => in_array($c->value, $this->dipilih, true)
                    && in_array($k, KategoriReset::lengkapi([$c]), true));

            $hasil[$k->value] = $penyebab?->label() ?? '';
        }

        return $hasil;
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function ringkasan(): array
    {
        return app(ResetDataService::class)->ringkasan($this->kategoriTerpilih);
    }

    #[Computed]
    public function alasanDitolak(): ?string
    {
        return app(ResetDataService::class)->alasanDitolak();
    }

    /**
     * @return list<array{nama: string, ukuran: int, waktu: int}>
     */
    #[Computed]
    public function daftarBackup(): array
    {
        return app(ResetDataService::class)->daftarBackup();
    }

    public function konfirmasi(): void
    {
        $this->ensureAdministrator();

        if ($this->kategoriTerpilih === []) {
            $this->addError('dipilih', 'Pilih minimal satu data yang akan dihapus.');

            return;
        }

        $this->reset(['frasa', 'password']);
        $this->resetErrorBag();
        $this->showKonfirmasi = true;
    }

    public function batal(): void
    {
        $this->showKonfirmasi = false;
        $this->reset(['frasa', 'password']);
    }

    public function jalankan(ResetDataService $service): void
    {
        $this->ensureAdministrator();

        $kunci = 'reset-data:'.auth()->id();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            throw ValidationException::withMessages([
                'password' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($kunci) / 60).' menit.',
            ]);
        }

        // Counted before validating so wrong passwords burn attempts too.
        RateLimiter::hit($kunci, 600);

        $this->validate([
            'frasa' => ['required', 'in:'.self::FRASA],
            'password' => ['required', 'current_password:web'],
        ], [
            'frasa.in' => 'Ketik persis: '.self::FRASA,
            'password.current_password' => 'Password salah.',
        ]);

        $this->hasil = $service->jalankan($this->dipilih, auth()->user());

        RateLimiter::clear($kunci);
        $this->reset(['dipilih', 'frasa', 'password', 'showKonfirmasi']);
        unset($this->kategoriTerpilih, $this->otomatis, $this->ringkasan, $this->daftarBackup);
    }

    /**
     * Route-level `role:administrator` middleware (routes/admin.php) already
     * keeps admin/operator out — defense-in-depth, as on Kelola Peran.
     */
    private function ensureAdministrator(): void
    {
        abort_unless(auth()->user()?->hasRole('administrator'), 403);
    }
};
