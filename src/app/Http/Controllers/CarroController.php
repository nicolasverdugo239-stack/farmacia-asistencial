<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsumoCarroRequest;
use App\Models\Carrito;
use App\Models\CarritoStock;
use App\Services\CarroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CarroController extends Controller
{
    public function index(): RedirectResponse
    {
        $carrito = Carrito::orderBy('id')->firstOrFail();

        return redirect()->route('carros.show', $carrito);
    }

    public function show(Carrito $carrito): View
    {
        $carrito->load(['secciones' => fn ($q) => $q->orderBy('tipo'), 'secciones.stock.producto']);

        return view('carros.show', ['carrito' => $carrito]);
    }

    public function consumo(ConsumoCarroRequest $request, CarritoStock $stock, CarroService $servicio): RedirectResponse
    {
        $servicio->consumir($stock, $request->integer('cantidad'), $request->user());

        return back()->with('ok', 'Consumo registrado.');
    }

    public function reposicion(CarritoStock $stock, CarroService $servicio): RedirectResponse
    {
        $this->authorize('gestionar carro');
        $servicio->reponer($stock, request()->user());

        return back()->with('ok', 'Sección repuesta desde bodega (lote FEFO).');
    }
}
