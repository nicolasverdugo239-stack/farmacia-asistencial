<?php

namespace App\Http\Controllers;

use App\Http\Requests\DispensarRequest;
use App\Models\Lote;
use App\Models\Paciente;
use App\Models\Receta;
use App\Rules\Rut;
use App\Services\DispensacionService;
use App\Services\FefoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispensacionController extends Controller
{
    public function buscar(Request $request): View|RedirectResponse
    {
        $termino = trim((string) $request->query('q', ''));
        if ($termino === '') {
            return view('dispensaciones.buscar', ['recetas' => collect(), 'termino' => '']);
        }

        $request->validate(['q' => ['string', 'max:20']]);

        // Se busca por folio o, si el texto es un RUT válido, por el hash del RUT del paciente.
        $query = Receta::with('paciente', 'programa')->where('estado', 'vigente');
        if (Rut::valido($termino)) {
            $paciente = Paciente::buscarPorRut($termino);
            $query->where('paciente_id', $paciente->id ?? 0);
        } else {
            $query->where('folio', $termino);
        }

        $recetas = $query->orderByDesc('fecha_emision')->get();

        if ($recetas->count() === 1) {
            return redirect()->route('dispensaciones.create', $recetas->first());
        }

        return view('dispensaciones.buscar', ['recetas' => $recetas, 'termino' => $termino]);
    }

    public function create(Receta $receta, FefoService $fefo): View
    {
        $receta->load('paciente', 'medico', 'programa', 'detalles.producto');

        $lineas = $receta->detalles->map(fn ($d) => [
            'detalle' => $d,
            'sugerido' => $fefo->loteSugerido($d->producto_id, $d->cantidad),
            'lotes' => Lote::disponiblesFefo($d->producto_id)->get(),
        ]);

        return view('dispensaciones.create', ['receta' => $receta, 'lineas' => $lineas]);
    }

    public function store(DispensarRequest $request, Receta $receta, DispensacionService $servicio): RedirectResponse
    {
        $dispensacion = $servicio->dispensar(
            $receta,
            $request->user(),
            array_map('intval', $request->input('lotes', [])),
            $request->tercero(),
        );

        return redirect()->route('dispensaciones.buscar')
            ->with('ok', "Dispensación D-".str_pad((string) $dispensacion->id, 6, '0', STR_PAD_LEFT)." registrada para la receta {$receta->folio}.");
    }
}
