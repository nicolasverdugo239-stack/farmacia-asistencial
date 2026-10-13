<?php

namespace App\Services;

use App\Models\CarritoMovimiento;
use App\Models\CarritoStock;
use App\Models\Lote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Carro de emergencia (HU11, RF14): cada sección (Paro / Unidosis) tiene su propio stock.
 * Un consumo descuenta solo su sección; una reposición saca unidades de bodega aplicando FEFO.
 */
class CarroService
{
    public function __construct(
        private FefoService $fefo,
        private AlertaService $alertas,
    ) {}

    public function consumir(CarritoStock $stock, int $cantidad, User $usuario): CarritoStock
    {
        return DB::transaction(function () use ($stock, $cantidad, $usuario) {
            $stock = CarritoStock::lockForUpdate()->findOrFail($stock->id);

            if ($stock->cantidad < $cantidad) {
                throw ValidationException::withMessages(['cantidad' => 'La sección no tiene esa cantidad disponible.']);
            }

            $stock->cantidad -= $cantidad;
            $stock->save();

            CarritoMovimiento::create([
                'carrito_stock_id' => $stock->id,
                'tipo' => 'consumo',
                'cantidad' => $cantidad,
                'user_id' => $usuario->id,
            ]);

            $this->alertas->revisarCarro($stock);

            return $stock;
        });
    }

    /** Repone hasta el máximo de la sección desde el lote FEFO de bodega. */
    public function reponer(CarritoStock $stock, User $usuario): CarritoStock
    {
        return DB::transaction(function () use ($stock, $usuario) {
            $stock = CarritoStock::lockForUpdate()->findOrFail($stock->id);
            $faltante = $stock->stock_maximo - $stock->cantidad;

            if ($faltante <= 0) {
                return $stock;
            }

            $sugerido = $this->fefo->loteSugerido($stock->producto_id, $faltante);
            if (! $sugerido) {
                throw ValidationException::withMessages(['cantidad' => 'No hay un lote en bodega con stock suficiente para reponer.']);
            }

            $lote = Lote::lockForUpdate()->findOrFail($sugerido->id);
            $lote->cantidad_actual -= $faltante;
            $lote->save();

            $stock->cantidad = $stock->stock_maximo;
            $stock->save();

            CarritoMovimiento::create([
                'carrito_stock_id' => $stock->id,
                'tipo' => 'reposicion',
                'cantidad' => $faltante,
                'lote_id' => $lote->id,
                'user_id' => $usuario->id,
            ]);

            return $stock;
        });
    }
}
