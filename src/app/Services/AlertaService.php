<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\CarritoStock;
use App\Models\Lote;
use App\Models\Producto;

class AlertaService
{
    /** Stock crítico (RF05 / HU05): una sola alerta abierta por producto. */
    public function revisarStock(Producto $producto): void
    {
        if ($producto->stockTotal() >= $producto->stock_minimo) {
            return;
        }
        $this->crearSiNoExiste('stock_critico', $producto->id, null,
            "{$producto->nombre}: stock bajo el mínimo ({$producto->stockTotal()} de {$producto->stock_minimo}).");
    }

    /** Vencimientos próximos (RF05). Devuelve cuántas alertas nuevas creó. */
    public function revisarVencimientos(int $dias = 60): int
    {
        $creadas = 0;
        $lotes = Lote::with('producto')
            ->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '<=', now()->addDays($dias)->toDateString())
            ->get();

        foreach ($lotes as $lote) {
            $texto = $lote->estaVencido() ? 'venció el' : 'vence el';
            $creadas += $this->crearSiNoExiste('vencimiento', $lote->producto_id, $lote->id,
                "Lote {$lote->numero_lote} de {$lote->producto->nombre} {$texto} {$lote->fecha_vencimiento->format('d-m-Y')}.") ? 1 : 0;
        }

        return $creadas;
    }

    /** Descuadre en controlados (RF18 / HU14). Siempre crea una alerta nueva. */
    public function descuadre(Producto $producto, int $esperado, int $registrado, string $origen): Alerta
    {
        $diferencia = $registrado - $esperado;

        return Alerta::create([
            'tipo' => 'descuadre',
            'producto_id' => $producto->id,
            'mensaje' => "{$producto->nombre}: {$origen} {$registrado}, esperado {$esperado} (diferencia ".
                ($diferencia > 0 ? '+' : '')."{$diferencia}).",
        ]);
    }

    /** Carro de emergencia bajo el mínimo en una sección (HU11). */
    public function revisarCarro(CarritoStock $stock): void
    {
        if (! $stock->bajoMinimo()) {
            return;
        }
        $stock->loadMissing('seccion.carrito', 'producto');
        $this->crearSiNoExiste('carro', $stock->producto_id, null,
            "Carro {$stock->seccion->carrito->codigo} · {$stock->seccion->nombre()}: {$stock->producto->nombre} bajo el mínimo ({$stock->cantidad} de {$stock->stock_minimo}).");
    }

    private function crearSiNoExiste(string $tipo, ?int $productoId, ?int $loteId, string $mensaje): bool
    {
        $existe = Alerta::where('tipo', $tipo)
            ->where('leida', false)
            ->where('producto_id', $productoId)
            ->where('lote_id', $loteId)
            ->exists();

        if (! $existe) {
            Alerta::create(['tipo' => $tipo, 'producto_id' => $productoId, 'lote_id' => $loteId, 'mensaje' => $mensaje]);
        }

        return ! $existe;
    }
}
