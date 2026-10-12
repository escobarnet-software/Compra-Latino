<?php

/*
 * StoreRegisterRequest: valida el registro.
 * Correo que no exista, contrasena de 8 o mas confirmada y plataforma valida.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegisterRequest extends FormRequest
{
    /**
     * El registro es público (invitados); 'guest' en rutas lo refuerza.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * REGLAS DECLARATIVAS del alta de cuenta multiplataforma.
     */
    public function rules(): array
    {
        return [
            // Nombre visible en la tienda y el panel BI.
            'name' => ['required', 'string', 'max:255'],

            // Identidad única: revisar antes de seguir antes del UNIQUE de la BD.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],

            // Secreto: longitud mínima + confirmación (campo password_confirmation).
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Plataforma preferida: dominio cerrado Fase III.
            'plataforma_preferida' => ['required', Rule::in(['local', 'yauctions'])],
        ];
    }

    /**
     * Mensajes en español (UX: retroalimentación comprensible).
     */
    public function messages(): array
    {
        return [
            'email.unique'      => 'Ese correo ya está registrado. Inicia sesión.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'plataforma_preferida.in' => 'La plataforma seleccionada no es válida.',
        ];
    }

    /**
     * Nombres amigables de atributos para los mensajes de error.
     */
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
