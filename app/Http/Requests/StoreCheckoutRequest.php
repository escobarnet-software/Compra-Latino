<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'cantidad'   => ['required', 'integer', 'min:1', 'max:10'],
            'plataforma' => ['required', Rule::in(['local', 'yauctions'])],

            'titular'      => ['required', 'string', 'max:100'],
            'numero'       => ['required', 'string', 'regex:/^[\d\s\-]{13,23}$/'],
            'vencimiento'  => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv'          => ['required', 'string', 'regex:/^\d{3,4}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'titular.required'     => 'Indica el nombre impreso en la tarjeta.',
            'numero.regex'         => 'El número de tarjeta debe tener entre 13 y 19 dígitos.',
            'vencimiento.regex'    => 'El vencimiento debe tener formato MM/AA (ej. 12/28).',
            'cvv.regex'            => 'El CVV debe tener 3 o 4 dígitos.',
            'plataforma.in'        => 'La plataforma seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id'  => 'producto',
            'cantidad'    => 'cantidad',
            'plataforma'  => 'plataforma de compra',
            'titular'     => 'titular de la tarjeta',
            'numero'      => 'número de tarjeta',
            'vencimiento' => 'vencimiento',
            'cvv'         => 'código de seguridad',
        ];
    }
}
