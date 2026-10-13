<?php

namespace Database\Factories;

use App\Rules\Rut;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $cuerpo = (string) fake()->unique()->numberBetween(10000000, 25000000);

        return [
            'rut' => $cuerpo.'-'.Rut::digitoVerificador($cuerpo),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Clave#Segura2026'),
            'activo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
