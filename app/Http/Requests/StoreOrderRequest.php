<?php

/*
 * StoreOrderRequest: valida una compra antes de que siga su camino.
 * Revisa que el producto exista, que la cantidad sea razonable y que la
 * plataforma sea 'local' o 'yauctions'.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * ¿PERMITE la solicitud este endpoint?
     * Se exige autenticación; el rol se maneja en las rutas (middleware).
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * REGLAS DECLARATIVAS — la validación es la PRIMERA línea de defensa.
     */
    public function rules(): array
    {
        return [
            // El producto debe existir en el catálogo (integridad referencial temprana).
            'product_id' => ['required', 'integer', 'exists:products,id'],

            // Cantidad: entero positivo con tope razonable (anti-automatización).
            'cantidad'   => ['required', 'integer', 'min:1', 'max:10'],

            // Multiplataforma: dominio cerrado; impide valores arbitrarios.
            'plataforma' => ['required', Rule::in(['local', 'yauctions'])],
        ];
    }

    /**
     * Mensajes de error personalizados (capacidad de personalización del Form
     * Request): separan el idioma/presentación del motor de validación.
     */
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

    /**
     * Nombres amigables de los atributos (mejora la accesibilidad del error).
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'cantidad'   => 'cantidad',
            'plataforma' => 'plataforma de compra',
        ];
    }
}
