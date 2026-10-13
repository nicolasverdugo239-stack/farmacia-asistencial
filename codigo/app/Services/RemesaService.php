<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\Producto;
use App\Models\Remesa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recepción de remesas CENABAST (HU02, RF09): crea los lotes y actualiza el stock.
 * Si la remesa trae controlados, el ingreso queda en el libro firmado por el QF.
 */
class RemesaService
{
    public function __construct(
        private LibroControladosService $libro,
    ) {}

    /**
     * @param  array<int, array{producto_id: int, numero_lote: string, fecha_vencimiento: string, cantidad: int}>  $lineas
     */
    public function recibir(string $numeroGuia, array $lineas, User $usuario): Remesa
    {
        $productos = Producto::whereIn('id', array_column($lineas, 'producto_id'))->get()->keyBy('id');

        if ($productos->contains(fn ($p) => $p->es_controlado) && ! $usuario->can('firmar controlados')) {
            throw ValidationException::withMessages([
                'lineas' => 'La remesa incluye controlados: su ingreso debe recibirlo y firmarlo el Químico farmacéutico.',
            ]);
        }

        return DB::transaction(function () use ($numeroGuia, $lineas, $usuario, $productos) {
            $remesa = Remesa::create([
                'numero_guia' => $numeroGuia,
                'fecha_recepcion' => now()->toDateString(),
                'recibido_por' => $usuario->id,
            ]);

            foreach ($lineas as $linea) {
                $lote = Lote::lockForUpdate()
                    ->where('producto_id', $linea['producto_id'])
                    ->where('numero_lote', $linea['numero_lote'])
                    ->first();

                if ($lote && $lote->fecha_vencimiento->toDateString() !== $linea['fecha_vencimiento']) {
                    throw ValidationException::withMessages([
                        'lineas' => "El lote {$linea['numero_lote']} ya existe con otra fecha de vencimiento. Revisa la guía.",
                    ]);
                }

                if ($lote) {
                    $lote->cantidad_actual += $linea['cantidad'];
                    $lote->save();
                } else {
                    $lote = Lote::create([
                        'producto_id' => $linea['producto_id'],
                        'remesa_id' => $remesa->id,
                        'numero_lote' => $linea['numero_lote'],
                        'fecha_vencimiento' => $linea['fecha_vencimiento'],
                        'cantidad_actual' => $linea['cantidad'],
                        'ubicacion' => 'bodega',
                    ]);
                }

                $producto = $productos[$linea['producto_id']];
                if ($producto->es_controlado) {
                    $this->libro->registrar($lote, 'ingreso', $linea['cantidad'], $usuario, $usuario,
                        "Remesa CENABAST guía {$numeroGuia}");
                }
            }

            return $remesa->load('lotes.producto');
        });
    }
}
