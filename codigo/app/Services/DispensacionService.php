<?php

namespace App\Services;

use App\Models\Dispensacion;
use App\Models\DispensacionDetalle;
use App\Models\Lote;
use App\Models\Receta;
use App\Models\RetiroTercero;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dispensación de una receta (HU03, HU04, HU12, RF06, RF07, RF16).
 * Todo ocurre en una transacción con bloqueo de filas: o se entrega todo o no se entrega nada,
 * y dos técnicos no pueden descontar el mismo stock al mismo tiempo.
 */
class DispensacionService
{
    public function __construct(
        private FefoService $fefo,
        private LibroControladosService $libro,
        private AlertaService $alertas,
    ) {}

    /**
     * @param  array<int, int>  $lotesElegidos  producto_id => lote_id (opcional; si falta se usa FEFO)
     * @param  array{nombre: string, rut: string, parentesco: string}|null  $tercero
     */
    public function dispensar(Receta $receta, User $usuario, array $lotesElegidos = [], ?array $tercero = null): Dispensacion
    {
        $receta->loadMissing('detalles.producto');

        if (! $receta->estaVigente()) {
            throw ValidationException::withMessages([
                'receta' => 'La receta no está vigente (vencida, anulada o ya dispensada).',
            ]);
        }

        $tieneControlados = $receta->detalles->contains(fn ($d) => $d->producto->es_controlado);
        if ($tieneControlados && ! $usuario->can('firmar controlados')) {
            throw new AuthorizationException('Esta receta incluye medicamentos controlados: debe dispensarla el Químico farmacéutico.');
        }

        return DB::transaction(function () use ($receta, $usuario, $lotesElegidos, $tercero) {
            // Se vuelve a leer la receta bloqueada para evitar una doble dispensación simultánea.
            $receta = Receta::lockForUpdate()->findOrFail($receta->id);
            if ($receta->estado !== 'vigente') {
                throw ValidationException::withMessages(['receta' => 'La receta ya fue dispensada.']);
            }

            $dispensacion = Dispensacion::create(['receta_id' => $receta->id, 'user_id' => $usuario->id]);

            foreach ($receta->detalles()->with('producto')->get() as $detalle) {
                $sugerido = $this->fefo->loteSugerido($detalle->producto_id, $detalle->cantidad);
                $loteId = $lotesElegidos[$detalle->producto_id] ?? $sugerido?->id;

                if (! $loteId) {
                    throw ValidationException::withMessages([
                        'lotes' => "No hay un lote vigente con stock suficiente de {$detalle->producto->nombre}.",
                    ]);
                }

                $lote = Lote::lockForUpdate()->findOrFail($loteId);

                if ((int) $lote->producto_id !== (int) $detalle->producto_id) {
                    throw ValidationException::withMessages(['lotes' => 'El lote elegido no corresponde al medicamento.']);
                }
                if ($lote->estaVencido()) {
                    throw ValidationException::withMessages(['lotes' => "El lote {$lote->numero_lote} está vencido."]);
                }
                if ($lote->cantidad_actual < $detalle->cantidad) {
                    throw ValidationException::withMessages(['lotes' => "Stock insuficiente en el lote {$lote->numero_lote}."]);
                }

                $lote->cantidad_actual -= $detalle->cantidad;
                $lote->save();

                DispensacionDetalle::create([
                    'dispensacion_id' => $dispensacion->id,
                    'lote_id' => $lote->id,
                    'cantidad' => $detalle->cantidad,
                    'fuera_de_fefo' => $sugerido !== null && (int) $sugerido->id !== (int) $lote->id,
                ]);

                if ($detalle->producto->es_controlado) {
                    $this->libro->registrar($lote, 'egreso', $detalle->cantidad, $usuario, $usuario,
                        "Dispensación receta {$receta->folio}");
                }

                $this->alertas->revisarStock($detalle->producto);
            }

            if ($tercero !== null) {
                RetiroTercero::create([
                    'dispensacion_id' => $dispensacion->id,
                    'nombre' => $tercero['nombre'],
                    'rut' => $tercero['rut'],
                    'parentesco' => $tercero['parentesco'],
                ]);
            }

            $receta->update(['estado' => 'dispensada']);

            return $dispensacion->load('detalles.lote.producto', 'retiroTercero');
        });
    }
}
