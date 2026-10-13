<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConteoFisicoRequest;
use App\Http\Requests\EgresoControladoRequest;
use App\Models\Lote;
use App\Models\MovimientoControlado;
use App\Models\Producto;
use App\Services\AlertaService;
use App\Services\LibroControladosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ControladosController extends Controller
{
    public function index(): View
    {
        return view('controlados.index', [
            'movimientos' => MovimientoControlado::with('lote.producto', 'registrador', 'autorizador')
                ->orderByDesc('id')->paginate(25),
            'lotes' => Lote::with('producto')
                ->whereHas('producto', fn ($q) => $q->where('es_controlado', true))
                ->where('cantidad_actual', '>', 0)->orderBy('fecha_vencimiento')->get(),
            'productos' => Producto::where('es_controlado', true)->orderBy('nombre')->get(),
        ]);
    }

    public function egreso(EgresoControladoRequest $request, LibroControladosService $libro): RedirectResponse
    {
        $mov = DB::transaction(function () use ($request, $libro) {
            $lote = Lote::lockForUpdate()->with('producto')->findOrFail($request->integer('lote_id'));

            if (! $lote->producto->es_controlado) {
                throw ValidationException::withMessages(['lote_id' => 'El lote no es de un medicamento controlado.']);
            }
            if ($lote->cantidad_actual < $request->integer('cantidad')) {
                throw ValidationException::withMessages(['cantidad' => "El lote solo tiene {$lote->cantidad_actual} unidades."]);
            }

            $lote->cantidad_actual -= $request->integer('cantidad');
            $lote->save();

            return $libro->registrar($lote, 'egreso', $request->integer('cantidad'),
                $request->user(), $request->user(), $request->string('observacion')->toString());
        });

        return back()->with('ok', 'Egreso firmado y registrado. Hash '.substr($mov->hash, 0, 12).'… encadenado al registro anterior.');
    }

    /** HU14: el QF ingresa el conteo físico y el sistema lo compara con el stock registrado. */
    public function conteo(ConteoFisicoRequest $request, AlertaService $alertas): RedirectResponse
    {
        $producto = Producto::findOrFail($request->integer('producto_id'));
        $sistema = $producto->stockTotal();
        $fisico = $request->integer('cantidad_fisica');

        if ($sistema === $fisico) {
            return back()->with('ok', "Conteo correcto: {$producto->nombre} cuadra ({$fisico} unidades).");
        }

        $alertas->descuadre($producto, $sistema, $fisico, 'conteo físico');

        return back()->withErrors(['conteo' => "Descuadre en {$producto->nombre}: físico {$fisico}, sistema {$sistema}. Se generó una alerta."]);
    }

    public function verificar(LibroControladosService $libro): RedirectResponse
    {
        $rota = $libro->verificarCadena();

        return $rota === null
            ? back()->with('ok', 'Cadena de hashes íntegra: ningún registro fue alterado.')
            : back()->withErrors(['cadena' => "¡Cadena rota en el registro #{$rota}! El libro fue modificado fuera del sistema."]);
    }
}
