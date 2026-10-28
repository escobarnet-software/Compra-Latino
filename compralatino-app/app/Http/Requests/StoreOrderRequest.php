<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Debe seleccionar un producto para comprar.',
            'product_id.exists'   => 'El producto seleccionado ya no está disponible.',
            'cantidad.min'        => 'La cantidad mínima permitida es 1 unidad.',
            'cantidad.max'        => 'No se permiten compras mayores a 10 unidades por transacción.',
            'plataforma.in'       => 'La plataforma seleccionada no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'cantidad'   => 'cantidad',
            'plataforma' => 'plataforma de compra',
        ];
    }
}
