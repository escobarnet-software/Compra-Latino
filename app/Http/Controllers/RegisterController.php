<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegisterRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $usuario = User::create([
            'name'                => $datos['name'],
            'email'               => $datos['email'],
            'password'            => Hash::make($datos['password']),
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
