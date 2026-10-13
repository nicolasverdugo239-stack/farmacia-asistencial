<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => User::with('roles')->orderBy('name')->get()]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $usuario = User::create($request->safe()->only(['rut', 'name', 'email', 'password']));
        $usuario->assignRole($request->validated('rol'));

        return back()->with('ok', "Usuario {$usuario->name} creado.");
    }

    public function toggle(User $usuario): RedirectResponse
    {
        $this->authorize('gestionar usuarios');

        if ($usuario->is(request()->user())) {
            return back()->withErrors(['usuario' => 'No puedes desactivar tu propia cuenta.']);
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado; su sesión se cerrará.');
    }
}
