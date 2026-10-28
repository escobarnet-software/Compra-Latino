<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegisterRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'plataforma_preferida' => ['required', Rule::in(['local', 'yauctions'])],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'      => 'Ese correo ya está registrado. Inicia sesión.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'plataforma_preferida.in' => 'La plataforma seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                => 'nombre',
            'email'               => 'correo electrónico',
            'password'            => 'contraseña',
            'plataforma_preferida' => 'plataforma preferida',
        ];
    }
}
