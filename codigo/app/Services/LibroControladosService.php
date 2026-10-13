<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\MovimientoControlado;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Libro digital de estupefacientes y psicotrópicos (RF08, HU06, HU14, HU15).
 *
 * Cada movimiento guarda:
 *   hash = SHA-256( hash_anterior | lote | tipo | cantidad | saldo | registra | autoriza | fecha )
 * Si alguien modifica o borra una fila directamente en la base de datos,
 * el hash deja de coincidir y verificarCadena() indica en qué fila se rompió.
 *
 * Este servicio NO mueve stock: quien lo llama descuenta o suma el lote dentro
 * de la misma transacción y luego registra el movimiento.
 */
class LibroControladosService
{
    public const HASH_INICIAL = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(private AlertaService $alertas) {}

    public function registrar(
        Lote $lote,
        string $tipo,
        int $cantidad,
        User $registra,
        User $autoriza,
        ?string $observacion = null,
    ): MovimientoControlado {
        $lote->loadMissing('producto');

        if (! $lote->producto->es_controlado) {
            throw new InvalidArgumentException('El producto no es un medicamento controlado.');
        }
        if (! in_array($tipo, ['ingreso', 'egreso', 'ajuste'], true)) {
            throw new InvalidArgumentException("Tipo de movimiento no válido: {$tipo}");
        }
        if (! $autoriza->can('firmar controlados')) {
            throw new AuthorizationException('Solo el Químico farmacéutico puede autorizar movimientos de controlados.');
        }

        return DB::transaction(function () use ($lote, $tipo, $cantidad, $registra, $autoriza, $observacion) {
            // Bloquea el último registro para que dos firmas simultáneas no compartan el mismo hash_anterior.
            $anterior = MovimientoControlado::lockForUpdate()->orderByDesc('id')->first();

            $saldo = (int) Lote::where('producto_id', $lote->producto_id)->sum('cantidad_actual');

            $datos = [
                'lote_id' => $lote->id,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'saldo_resultante' => $saldo,
                'observacion' => $observacion,
                'registrado_por' => $registra->id,
                'autorizado_por' => $autoriza->id,
                'hash_anterior' => $anterior->hash ?? self::HASH_INICIAL,
                'created_at' => now()->startOfSecond(),
                'updated_at' => now()->startOfSecond(),
            ];
            $datos['hash'] = self::calcularHash($datos);

            $this->detectarDescuadre($lote, $tipo, $cantidad, $saldo);

            return MovimientoControlado::create($datos);
        });
    }

    public static function calcularHash(array $d): string
    {
        return hash('sha256', implode('|', [
            $d['hash_anterior'],
            $d['lote_id'],
            $d['tipo'],
            $d['cantidad'],
            $d['saldo_resultante'],
            $d['registrado_por'],
            $d['autorizado_por'],
            Carbon::parse($d['created_at'])->format('Y-m-d H:i:s'),
        ]));
    }

    /**
     * Recorre el libro completo y recalcula cada hash.
     * Devuelve el id de la primera fila alterada, o null si la cadena está íntegra.
     */
    public function verificarCadena(): ?int
    {
        $anterior = self::HASH_INICIAL;

        foreach (MovimientoControlado::orderBy('id')->cursor() as $mov) {
            $datos = $mov->getAttributes();
            if ($mov->hash_anterior !== $anterior || self::calcularHash($datos) !== $mov->hash) {
                return $mov->id;
            }
            $anterior = $mov->hash;
        }

        return null;
    }

    /**
     * HU14: compara el saldo esperado (último saldo del libro ± este movimiento)
     * con el stock que realmente registra el sistema. Si no coinciden, alerta.
     */
    private function detectarDescuadre(Lote $lote, string $tipo, int $cantidad, int $saldoActual): void
    {
        $ultimo = MovimientoControlado::whereIn('lote_id', Lote::where('producto_id', $lote->producto_id)->select('id'))
            ->orderByDesc('id')
            ->first();

        if (! $ultimo || $tipo === 'ajuste') {
            return;
        }

        $esperado = $tipo === 'ingreso'
            ? $ultimo->saldo_resultante + $cantidad
            : $ultimo->saldo_resultante - $cantidad;

        if ($esperado !== $saldoActual) {
            $producto = Producto::findOrFail($lote->producto_id);
            $this->alertas->descuadre($producto, $esperado, $saldoActual, 'stock en sistema');
        }
    }
}
