<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\CatatAktivitas;
use App\Models\Concerns\PunyaLoginTerakhir;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['username', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use CatatAktivitas, HasFactory, HasRoles, Notifiable, PunyaLoginTerakhir;

    protected function atributDicatat(): array
    {
        return ['name', 'username', 'email', 'password'];
    }

    protected function jenisAktivitas(): string
    {
        return 'Pengguna';
    }

    public function labelAktivitas(): string
    {
        return "{$this->name} ({$this->username})";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
