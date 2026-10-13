<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Dispensacion;
use App\Models\Lote;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'descuadres' => Alerta::where('tipo', 'descuadre')->where('leida', false)->count(),
            'stockCritico' => Alerta::whereIn('tipo', ['stock_critico', 'carro'])->where('leida', false)->count(),
            'porVencer' => Lote::where('cantidad_actual', '>', 0)
                ->whereDate('fecha_vencimiento', '<=', now()->addDays(60)->toDateString())->count(),
            'dispensacionesHoy' => Dispensacion::whereDate('created_at', now()->toDateString())->count(),
            'alertas' => Alerta::where('leida', false)
                ->orderByRaw("CASE tipo WHEN 'descuadre' THEN 0 WHEN 'carro' THEN 1 WHEN 'stock_critico' THEN 2 ELSE 3 END")
                ->latest()->limit(20)->get(),
        ]);
    }

    public function marcarLeida(Alerta $alerta)
    {
        $alerta->update(['leida' => true]);

        return back();
    }
}
