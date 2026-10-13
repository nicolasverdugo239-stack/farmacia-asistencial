<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemesaRequest;
use App\Models\Producto;
use App\Services\RemesaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RemesaController extends Controller
{
    public function create(): View
    {
        return view('remesas.create', [
            'productos' => Producto::orderBy('nombre')->get(['id', 'nombre', 'presentacion', 'es_controlado']),
        ]);
    }

    public function store(RemesaRequest $request, RemesaService $servicio): RedirectResponse
    {
        $remesa = $servicio->recibir(
            $request->string('numero_guia')->toString(),
            $request->validated('lineas'),
            $request->user(),
        );

        return redirect()->route('remesas.create')
            ->with('ok', "Remesa {$remesa->numero_guia} recibida: {$remesa->lotes->count()} lotes nuevos, stock actualizado.");
    }
}
