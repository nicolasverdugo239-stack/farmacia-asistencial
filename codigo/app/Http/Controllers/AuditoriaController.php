<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;
use Throwable;

class AuditoriaController extends Controller
{
    public function __invoke(): View
    {
        try {
            $eventos = config('auditoria.driver') === 'mongodb'
                ? AuditLog::orderBy('fecha', 'desc')->limit(100)->get()
                : collect();
            $error = null;
        } catch (Throwable) {
            $eventos = collect();
            $error = 'No se pudo conectar con MongoDB.';
        }

        return view('auditoria.index', ['eventos' => $eventos, 'error' => $error]);
    }
}
