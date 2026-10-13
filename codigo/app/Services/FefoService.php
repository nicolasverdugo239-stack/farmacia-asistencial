<?php

namespace App\Services;

use App\Models\Lote;

/**
 * FEFO (First Expired, First Out): se entrega primero el lote que vence antes,
 * siempre que no esté vencido y tenga stock suficiente.
 */
class FefoService
{
    public function loteSugerido(int $productoId, int $cantidad): ?Lote
    {
        return Lote::disponiblesFefo($productoId)
            ->where('cantidad_actual', '>=', $cantidad)
            ->first();
    }
}
