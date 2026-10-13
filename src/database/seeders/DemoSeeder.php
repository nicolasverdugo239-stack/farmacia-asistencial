<?php

namespace Database\Seeders;

use App\Models\Carrito;
use App\Models\CarritoSeccion;
use App\Models\CarritoStock;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Programa;
use App\Models\Receta;
use App\Models\User;
use App\Rules\Rut;
use App\Services\LibroControladosService;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo para la demo y la defensa. Todos los nombres y RUT son ficticios.
 */
class DemoSeeder extends Seeder
{
    public function run(LibroControladosService $libro): void
    {
        $clave = env('DEMO_PASSWORD', 'Farmacia#Demo2026');

        $rut = fn (string $cuerpo) => $cuerpo.'-'.Rut::digitoVerificador($cuerpo);

        User::create(['rut' => $rut('15764832'), 'name' => 'Ana Muñoz', 'email' => 'admin@farmacia.test', 'password' => $clave])
            ->assignRole('administrador');
        User::create(['rut' => $rut('19283471'), 'name' => 'Tomás Rivas', 'email' => 'tecnico@farmacia.test', 'password' => $clave])
            ->assignRole('tecnico');
        $qf = User::create(['rut' => $rut('16482915'), 'name' => 'Paula Soto', 'email' => 'quimico@farmacia.test', 'password' => $clave]);
        $qf->assignRole('quimico');

        $cronicos = Categoria::create(['nombre' => 'Crónicos']);
        $urgencia = Categoria::create(['nombre' => 'Urgencia']);
        $controlados = Categoria::create(['nombre' => 'Controlados']);
        $generales = Categoria::create(['nombre' => 'Generales']);

        $losartan = Producto::create(['categoria_id' => $cronicos->id, 'codigo_barras' => '7800000000011', 'nombre' => 'Losartán 50 mg', 'principio_activo' => 'Losartán potásico', 'presentacion' => 'Comprimidos', 'stock_minimo' => 100]);
        $metformina = Producto::create(['categoria_id' => $cronicos->id, 'codigo_barras' => '7800000000028', 'nombre' => 'Metformina 850 mg', 'principio_activo' => 'Metformina', 'presentacion' => 'Comprimidos', 'stock_minimo' => 150]);
        $paracetamol = Producto::create(['categoria_id' => $generales->id, 'codigo_barras' => '7800000000035', 'nombre' => 'Paracetamol 500 mg', 'principio_activo' => 'Paracetamol', 'presentacion' => 'Comprimidos', 'stock_minimo' => 200]);
        $omeprazol = Producto::create(['categoria_id' => $generales->id, 'codigo_barras' => '7800000000042', 'nombre' => 'Omeprazol 20 mg', 'principio_activo' => 'Omeprazol', 'presentacion' => 'Cápsulas', 'stock_minimo' => 100]);
        $adrenalina = Producto::create(['categoria_id' => $urgencia->id, 'codigo_barras' => '7800000000059', 'nombre' => 'Adrenalina 1 mg/ml', 'principio_activo' => 'Epinefrina', 'presentacion' => 'Ampolla', 'stock_minimo' => 20]);
        $atropina = Producto::create(['categoria_id' => $urgencia->id, 'codigo_barras' => '7800000000066', 'nombre' => 'Atropina 1 mg/ml', 'principio_activo' => 'Atropina', 'presentacion' => 'Ampolla', 'stock_minimo' => 10]);
        $morfina = Producto::create(['categoria_id' => $controlados->id, 'codigo_barras' => '7800000000073', 'nombre' => 'Morfina 10 mg/ml', 'principio_activo' => 'Morfina clorhidrato', 'presentacion' => 'Ampolla', 'es_controlado' => true, 'tipo_control' => 'estupefaciente', 'stock_minimo' => 10]);
        $clonazepam = Producto::create(['categoria_id' => $controlados->id, 'codigo_barras' => '7800000000080', 'nombre' => 'Clonazepam 2 mg', 'principio_activo' => 'Clonazepam', 'presentacion' => 'Comprimidos', 'es_controlado' => true, 'tipo_control' => 'psicotropico', 'stock_minimo' => 30]);

        $lote = fn (Producto $p, string $n, string $venc, int $cant) => Lote::create([
            'producto_id' => $p->id, 'numero_lote' => $n, 'fecha_vencimiento' => $venc, 'cantidad_actual' => $cant, 'ubicacion' => 'bodega',
        ]);
        $lote($losartan, 'L-2311', now()->addDays(45)->toDateString(), 118);
        $lote($losartan, 'L-2402', now()->addMonths(18)->toDateString(), 240);
        $lote($metformina, 'M-0915', now()->addMonths(9)->toDateString(), 300);
        $lote($paracetamol, 'P-5521', now()->addYears(2)->toDateString(), 500);
        $lote($omeprazol, 'O-3310', now()->addMonths(14)->toDateString(), 160);
        $lote($adrenalina, 'AD-118', now()->addMonths(11)->toDateString(), 40);
        $lote($atropina, 'AT-077', now()->addMonths(7)->toDateString(), 25);

        // Controlados: su existencia inicial entra al libro como ingreso firmado por el QF.
        $libro->registrar($lote($morfina, 'MF-2209', now()->addMonths(10)->toDateString(), 20), 'ingreso', 20, $qf, $qf, 'Saldo inicial');
        $libro->registrar($lote($clonazepam, 'CZ-4410', now()->addMonths(16)->toDateString(), 60), 'ingreso', 60, $qf, $qf, 'Saldo inicial');

        $carro = Carrito::create(['codigo' => 'BOX-1', 'servicio' => 'Box de urgencia 1']);
        $paro = CarritoSeccion::create(['carrito_id' => $carro->id, 'tipo' => 'paro']);
        $unidosis = CarritoSeccion::create(['carrito_id' => $carro->id, 'tipo' => 'unidosis']);
        CarritoStock::create(['carrito_seccion_id' => $paro->id, 'producto_id' => $adrenalina->id, 'cantidad' => 8, 'stock_minimo' => 6, 'stock_maximo' => 10]);
        CarritoStock::create(['carrito_seccion_id' => $paro->id, 'producto_id' => $atropina->id, 'cantidad' => 3, 'stock_minimo' => 4, 'stock_maximo' => 6]);
        CarritoStock::create(['carrito_seccion_id' => $unidosis->id, 'producto_id' => $paracetamol->id, 'cantidad' => 18, 'stock_minimo' => 10, 'stock_maximo' => 24]);
        CarritoStock::create(['carrito_seccion_id' => $unidosis->id, 'producto_id' => $omeprazol->id, 'cantidad' => 9, 'stock_minimo' => 8, 'stock_maximo' => 16]);

        $ges = Programa::create(['nombre' => 'GES · Hipertensión', 'sin_costo' => true]);
        Programa::create(['nombre' => 'Crónicos', 'sin_costo' => true]);
        $medico = Medico::create(['rut' => $rut('10234567'), 'nombre' => 'Dr. Andrés Pino']);
        $paciente = Paciente::create(['rut' => '12345678-5', 'nombre' => 'María González R.', 'telefono' => '+56 9 1234 5678']);

        $receta = Receta::create([
            'folio' => '10452', 'paciente_id' => $paciente->id, 'medico_id' => $medico->id, 'programa_id' => $ges->id,
            'fecha_emision' => now()->subDays(3)->toDateString(), 'fecha_vencimiento' => now()->addDays(17)->toDateString(),
        ]);
        $receta->detalles()->create(['producto_id' => $losartan->id, 'dosis' => '1 comprimido cada 24 horas', 'cantidad' => 30]);

        $recetaControlada = Receta::create([
            'folio' => '10453', 'paciente_id' => $paciente->id, 'medico_id' => $medico->id, 'programa_id' => null,
            'fecha_emision' => now()->subDay()->toDateString(), 'fecha_vencimiento' => now()->addDays(29)->toDateString(),
        ]);
        $recetaControlada->detalles()->create(['producto_id' => $clonazepam->id, 'dosis' => '1 comprimido en la noche', 'cantidad' => 30]);
    }
}
