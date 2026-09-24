<?php

use Spatie\Permission\Models\Role;

new class extends \Livewire\Component
{
    /**
     * The two roles whose permissions this page lets administrator edit.
     * `administrator` isn't here — it bypasses every permission check via
     * Gate::before (see AppServiceProvider), so toggling anything for it
     * would have no effect and would just be misleading to show as editable.
     *
     * @var list<string>
     */
    private const EDITABLE_ROLES = ['admin', 'operator'];

    /**
     * Every permission an administrator can hand to admin/operator from
     * this page, with a human label — keep in sync with what the app
     * actually checks (routes/admin.php `permission:` groups, `@can`/
     * `$this->authorize()` calls across the admin Livewire components).
     *
     * Deliberately excludes `users.manage`: it lets its holder create
     * accounts and assign them ANY role, including `administrator` — an
     * admin/operator who somehow got it could grant themselves full
     * access. It stays permanently exclusive to `administrator`, shown
     * on this page as a locked row rather than hidden outright.
     *
     * @var array<string, string>
     */
    private const PERMISSION_LABELS = [
        'master-data.manage' => 'Kelola Master Data (Jenjang, Pelajaran)',
        'bank-soal.manage' => 'Kelola Bank Soal',
        'ujian.manage' => 'Kelola Ujian (buat/ubah/hapus)',
        'ujian.view' => 'Lihat Ujian',
        'ujian-peserta.manage' => 'Kelola Peserta per Ujian (daftar/reset progres)',
        'leaderboard.view' => 'Lihat Leaderboard',
        'peserta.manage' => 'Kelola Peserta',
        'kartu-peserta.print' => 'Cetak Kartu Peserta',
    ];

    /**
     * [roleName => [safeKey(permission) => bool]] — the checkbox state.
     * Keyed by safeKey(), not the raw permission name: Livewire resolves
     * `wire:model="checked.admin.bank-soal.manage"` by exploding the WHOLE
     * string on ".", so a permission name that itself contains a dot (most
     * of them do) would get misread as extra nesting levels and silently
     * bind to the wrong place. safeKey() sidesteps that by swapping "." for
     * "__" in the leaf segment only.
     *
     * @var array<string, array<string, bool>>
     */
    public array $checked = [];

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->ensureAdministrator();

        foreach (self::EDITABLE_ROLES as $roleName) {
            $granted = Role::findOrCreate($roleName, 'web')->permissions->pluck('name')->all();

            foreach (self::PERMISSION_LABELS as $permission => $label) {
                $this->checked[$roleName][self::safeKey($permission)] = in_array($permission, $granted, true);
            }
        }
    }

    /**
     * safeKey(permission) => label, for the blade to loop over and bind
     * checkboxes to without hitting the dot-in-key issue described above.
     *
     * @return array<string, string>
     */
    public function permissionRows(): array
    {
        $rows = [];

        foreach (self::PERMISSION_LABELS as $permission => $label) {
            $rows[self::safeKey($permission)] = $label;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function editableRoles(): array
    {
        return self::EDITABLE_ROLES;
    }

    public function save(): void
    {
        $this->ensureAdministrator();

        foreach (self::EDITABLE_ROLES as $roleName) {
            $checkedKeys = collect($this->checked[$roleName] ?? [])->filter()->keys();

            // Recomputed from the fixed PERMISSION_LABELS catalog, not read
            // back from $checked's own keys: $checked is a public Livewire
            // property, so nothing stops a tampered request from adding an
            // extra key (e.g. safeKey('users.manage')) that was never in
            // the catalog to begin with — only permissions that genuinely
            // exist here are ever eligible to sync.
            $granted = collect(self::PERMISSION_LABELS)
                ->keys()
                ->filter(fn (string $permission) => $checkedKeys->contains(self::safeKey($permission)))
                ->all();

            Role::findOrCreate($roleName, 'web')->syncPermissions($granted);
        }

        $this->statusMessage = 'Pengaturan peran disimpan.';
    }

    private static function safeKey(string $permission): string
    {
        return str_replace('.', '__', $permission);
    }

    /**
     * Route-level `role:administrator` middleware (routes/admin.php)
     * already keeps admin/operator out — this is defense-in-depth in case
     * the component is ever rendered from somewhere that skips it.
     */
    private function ensureAdministrator(): void
    {
        abort_unless(auth()->user()?->hasRole('administrator'), 403);
    }
};
