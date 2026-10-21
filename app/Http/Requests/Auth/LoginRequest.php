<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Failed attempts allowed per username + IP before a lockout. */
    public const MAKS_PERCOBAAN = 5;

    /** How long a lockout lasts, in seconds. */
    public const DURASI_BLOKIR = 60;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Refuses the attempt outright while this username + IP is locked out.
     * Keyed on both, not IP alone: a whole school lab typically shares one
     * public IP, and one student's typos must not lock out the room.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAKS_PERCOBAAN)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'username' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($this->throttleKey()).' detik.',
        ]);
    }

    public function catatGagal(): void
    {
        RateLimiter::hit($this->throttleKey(), self::DURASI_BLOKIR);
    }

    public function bersihkanPercobaan(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Separate counters for the student and admin login forms, since the
     * same `username` input means a noreg on one and a username on the other.
     */
    public function throttleKey(): string
    {
        return Str::transliterate($this->path().'|'.Str::lower($this->string('username')).'|'.$this->ip());
    }
}
