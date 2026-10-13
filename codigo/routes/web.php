<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarroController;
use App\Http\Controllers\ControladosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispensacionController;
use App\Http\Controllers\RemesaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
 | Cada ruta exige sesión iniciada, usuario activo y el permiso concreto de la acción.
 | La autorización se valida en el servidor; ocultar botones en la vista no basta (OWASP A01).
 */

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware(['auth', 'activo'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/alertas/{alerta}/leida', [DashboardController::class, 'marcarLeida'])->name('alertas.leida');

    Route::middleware('permission:dispensar')->group(function () {
        Route::get('/dispensaciones', [DispensacionController::class, 'buscar'])->name('dispensaciones.buscar');
        Route::get('/recetas/{receta}/dispensar', [DispensacionController::class, 'create'])->name('dispensaciones.create');
        Route::post('/recetas/{receta}/dispensar', [DispensacionController::class, 'store'])->name('dispensaciones.store');
    });

    Route::middleware('permission:recibir remesas')->group(function () {
        Route::get('/remesas/nueva', [RemesaController::class, 'create'])->name('remesas.create');
        Route::post('/remesas', [RemesaController::class, 'store'])->name('remesas.store');
    });

    Route::middleware('permission:gestionar carro')->group(function () {
        Route::get('/carros', [CarroController::class, 'index'])->name('carros.index');
        Route::get('/carros/{carrito}', [CarroController::class, 'show'])->name('carros.show');
        Route::post('/carro-stock/{stock}/consumo', [CarroController::class, 'consumo'])->name('carros.consumo');
        Route::post('/carro-stock/{stock}/reposicion', [CarroController::class, 'reposicion'])->name('carros.reposicion');
    });

    Route::get('/controlados', [ControladosController::class, 'index'])
        ->middleware('permission:ver libro controlados')->name('controlados.index');
    Route::get('/controlados/verificar', [ControladosController::class, 'verificar'])
        ->middleware('permission:ver libro controlados')->name('controlados.verificar');
    Route::middleware('permission:firmar controlados')->group(function () {
        Route::post('/controlados/egreso', [ControladosController::class, 'egreso'])->name('controlados.egreso');
        Route::post('/controlados/conteo', [ControladosController::class, 'conteo'])->name('controlados.conteo');
    });

    Route::middleware('permission:gestionar usuarios')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'toggle'])->name('usuarios.toggle');
    });

    Route::get('/auditoria', AuditoriaController::class)
        ->middleware('permission:ver auditoria')->name('auditoria.index');
});
