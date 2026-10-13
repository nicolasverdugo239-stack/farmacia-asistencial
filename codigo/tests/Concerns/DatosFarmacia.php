<?php

namespace Tests\Concerns;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\User;
use App\Services\AuditoriaService;
use Database\Seeders\RolesPermisosSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Datos mínimos para las pruebas: roles, productos, lotes y recetas.
 */
trait DatosFarmacia
{
    protected function prepararRoles(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        AuditoriaService::limpiar();
    }

    protected function usuario(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    protected function producto(bool $controlado = false, int $stockMinimo = 0): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Pruebas']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $controlado ? 'Morfina 10 mg/ml' : 'Losartán 50 mg',
            'principio_activo' => $controlado ? 'Morfina' : 'Losartán',
            'presentacion' => $controlado ? 'Ampolla' : 'Comprimidos',
            'es_controlado' => $controlado,
            'tipo_control' => $controlado ? 'estupefaciente' : null,
            'stock_minimo' => $stockMinimo,
        ]);
    }

    protected function lote(Producto $producto, string $numero, int $diasParaVencer, int $cantidad): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id,
            'numero_lote' => $numero,
            'fecha_vencimiento' => now()->addDays($diasParaVencer)->toDateString(),
            'cantidad_actual' => $cantidad,
        ]);
    }

    protected function receta(Producto $producto, int $cantidad, int $diasVigencia = 10, string $folio = '10452'): Receta
    {
        $paciente = Paciente::create(['rut' => '12.345.678-5', 'nombre' => 'Paciente de prueba']);
        $medico = Medico::firstOrCreate(['rut' => '10234567-3'], ['nombre' => 'Dr. Prueba']);

        $receta = Receta::create([
            'folio' => $folio,
            'paciente_id' => $paciente->id,
            'medico_id' => $medico->id,
            'fecha_emision' => now()->subDay()->toDateString(),
            'fecha_vencimiento' => now()->addDays($diasVigencia)->toDateString(),
        ]);
        $receta->detalles()->create(['producto_id' => $producto->id, 'dosis' => '1 al día', 'cantidad' => $cantidad]);

        return $receta;
    }
}
