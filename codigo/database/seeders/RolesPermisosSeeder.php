<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matriz de roles y permisos del sistema (mínimo privilegio + separación de funciones):
 * el Administrador gestiona cuentas pero no mueve medicamentos.
 */
class RolesPermisosSeeder extends Seeder
{
    public const MATRIZ = [
        'administrador' => ['gestionar usuarios', 'ver libro controlados', 'ver dashboard', 'ver auditoria'],
        'tecnico' => ['recibir remesas', 'dispensar', 'gestionar carro'],
        'quimico' => ['recibir remesas', 'dispensar', 'gestionar carro', 'firmar controlados', 'ver libro controlados', 'ver dashboard'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = collect(self::MATRIZ)->flatten()->unique();
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (self::MATRIZ as $rol => $permisosDelRol) {
            Role::findOrCreate($rol, 'web')->syncPermissions($permisosDelRol);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
