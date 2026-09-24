<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    /**
     * The three /admin-tier roles this page can assign. `peserta` accounts
     * are managed on the Peserta page, not here.
     *
     * @var array<string, string>
     */
    private const ROLES = [
        'administrator' => 'Administrator',
        'admin' => 'Admin',
        'operator' => 'Operator',
    ];

    public ?int $editingId = null;

    public string $nama = '';

    public string $username = '';

    // Optional — only needed to let this account use "Login dengan Google"
    // on /admin/login (see App\Http\Controllers\Auth\GoogleAuthController).
    // Unset, that login path simply won't find a match for this user.
    public string $email = '';

    public string $password = '';

    public string $role = '';

    public bool $showModal = false;

    public string $search = '';

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    #[Computed]
    public function users(): Collection
    {
        // Not User::role(...): Spatie's `role` scope throws if any named
        // role doesn't exist yet in the roles table, which would 500 this
        // whole page on a partially-seeded install. Filtering by role_id
        // instead degrades to "no matching users" if one is missing.
        $roleIds = Role::query()->whereIn('name', array_keys(self::ROLES))->pluck('id');

        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function rolePilihan(): array
    {
        return self::ROLES;
    }

    public function create(): void
    {
        $this->authorize('users.manage');

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('users.manage');

        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->nama = $user->name;
        $this->username = $user->username;
        $this->email = $user->email ?? '';
        $this->password = '';
        $this->role = $user->getRoleNames()->first() ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('users.manage');

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->editingId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:6'],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);

            if ($this->menurunkanAdministratorTerakhir($user, $data['role'])) {
                $this->errorMessage = 'Tidak bisa mengubah role Administrator terakhir — akan ada admin yang terkunci dari sistem.';

                return;
            }

            $user->update(array_filter([
                'name' => $data['nama'],
                'username' => $data['username'],
                'password' => $data['password'] ? Hash::make($data['password']) : null,
            ]) + [
                // Not folded into the array_filter() above: unlike name/
                // username/password, an empty value here is a real action
                // (clearing the email un-links Google login for this
                // account), not "leave unchanged".
                'email' => $data['email'] ?: null,
            ]);
            $user->syncRoles([$data['role']]);

            $this->statusMessage = 'Pengguna diperbarui.';
        } else {
            $user = User::create([
                'name' => $data['nama'],
                'username' => $data['username'],
                'email' => $data['email'] ?: null,
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole($data['role']);

            $this->statusMessage = 'Pengguna ditambahkan.';
        }

        $this->errorMessage = null;
        $this->closeModal();
        unset($this->users);
    }

    public function delete(int $id): void
    {
        $this->authorize('users.manage');

        if ($id === auth()->id()) {
            $this->errorMessage = 'Tidak bisa menghapus akun sendiri.';

            return;
        }

        $user = User::findOrFail($id);

        if ($user->hasRole('administrator') && User::role('administrator')->count() <= 1) {
            $this->errorMessage = 'Tidak bisa menghapus Administrator terakhir — akan ada admin yang terkunci dari sistem.';

            return;
        }

        $user->delete();
        $this->statusMessage = 'Pengguna dihapus.';
        $this->errorMessage = null;
        unset($this->users);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * True when changing $user's role away from administrator would leave
     * the system with zero administrators.
     */
    private function menurunkanAdministratorTerakhir(User $user, string $roleBaru): bool
    {
        return $user->hasRole('administrator')
            && $roleBaru !== 'administrator'
            && User::role('administrator')->count() <= 1;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nama', 'username', 'email', 'password', 'role']);
        $this->resetErrorBag();
    }
};
