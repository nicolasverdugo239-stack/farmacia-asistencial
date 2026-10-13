<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Inicio de sesión con bloqueo por fuerza bruta (OWASP A07):
 * 5 intentos fallidos por correo + IP bloquean el acceso 15 minutos.
 */
class LoginRequest extends FormRequest
{
    public const MAX_INTENTOS = 5;

    public const BLOQUEO_SEGUNDOS = 900;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function authenticate(): void
    {
        $this->asegurarQueNoEstaBloqueado();

        // Solo usuarios activos pueden iniciar sesión.
        $credenciales = $this->only('email', 'password') + ['activo' => true];

        if (! Auth::attempt($credenciales)) {
            RateLimiter::hit($this->claveLimite(), self::BLOQUEO_SEGUNDOS);

            // Mensaje genérico: no revela si el correo existe.
            throw ValidationException::withMessages(['email' => 'Credenciales incorrectas.']);
        }

        RateLimiter::clear($this->claveLimite());
    }

    private function asegurarQueNoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveLimite(), self::MAX_INTENTOS)) {
            return;
        }

        event(new Lockout($this));
        $minutos = (int) ceil(RateLimiter::availableIn($this->claveLimite()) / 60);

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minutos.",
        ]);
    }

    private function claveLimite(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
