<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Conexión MongoDB para la bitácora de auditoría (base no estructurada).
        config(['database.connections.mongodb' => [
            'driver' => 'mongodb',
            'dsn' => config('auditoria.mongodb_uri'),
            'database' => config('auditoria.mongodb_database'),
        ]]);
    }

    public function boot(): void
    {
        // Política de contraseñas: 12+ caracteres, mayúsculas y minúsculas, números y símbolos.
        Password::defaults(function () {
            $regla = Password::min(12)->mixedCase()->numbers()->symbols();

            // En producción además se rechazan contraseñas filtradas (consulta anónima k-anonimato).
            return $this->app->isProduction() ? $regla->uncompromised() : $regla;
        });

        // Denegar por defecto: si ninguna regla lo permite, no se permite.
        Gate::before(fn (User $user) => $user->activo ? null : false);
    }
}
