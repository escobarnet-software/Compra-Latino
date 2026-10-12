<?php

/*
 * RegisterController: alta de cuentas.
 * Crea el usuario con la contrasena hasheada, lo deja como 'cliente' y ya lo
 * deja logueado. El rol de admin solo sale del seeder.
 */

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /** Formulario público de alta de cuenta. */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Alta de cuenta multiplataforma: crea el usuario, inicia sesión y lo
     * lleva a la tienda (o al panel BI si fuese administrador).
     */
    public function store(StoreRegisterRequest $request): RedirectResponse
    {
        // Solo datos validados cruzan hacia la Capa de Persistencia.
        $datos = $request->validated();

        $usuario = User::create([
            'name'                => $datos['name'],
            'email'               => $datos['email'],
            // NUNCA en claro: hash irreversible antes de persistir.
            'password'            => Hash::make($datos['password']),
            // Rol seguro por defecto (anti-escalada de privilegios).
            'rol'                 => 'cliente',
            'plataforma_preferida' => $datos['plataforma_preferida'],
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()
            ->intended($usuario->esAdministrador() ? route('bi.dashboard') : route('store.index'))
            ->with('status', "¡Bienvenido, {$usuario->name}! Tu cuenta quedó vinculada a la plataforma {$usuario->plataforma_preferida}.");
    }
}
