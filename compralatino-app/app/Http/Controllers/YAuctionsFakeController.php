<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YAuctionsFakeController extends Controller
{
    
    public function catalogo(Request $request): JsonResponse
    {
        $productos = Product::query()
            ->visibles()
            ->where('plataforma', 'yauctions')
            ->when($request->filled('query'), function ($query) use ($request) {
                $termino = '%' . str_replace(['%', '_'], '', (string) $request->string('query')) . '%';
                $query->where(function ($sub) use ($termino) {
                    $sub->where('nombre', 'like', $termino)
                        ->orWhere('sku', 'like', $termino);
                });
            })
            ->orderBy('nombre')
            ->limit(50)
            ->get(['id', 'sku', 'nombre', 'precio', 'moneda', 'external_id', 'stock', 'categoria']);

        return response()->json([
            'proveedor'  => 'YAuctions (simulador local)',
            'moneda'     => 'JPY',
            'total'      => $productos->count(),
            'productos'  => $productos,
        ]);
    }

    public function comprar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'external_product_id' => ['required', 'string', 'max:100'],
            'quantity'            => ['required', 'integer', 'min:1', 'max:10'],
            'buyer_reference'     => ['required', 'string', 'max:100'],
        ]);

        $producto = Product::where('external_id', $datos['external_product_id'])
            ->where('plataforma', 'yauctions')
            ->first();

        if (! $producto) {
            return response()->json([
                'status'  => 'no_encontrado',
                'mensaje' => "El producto externo '{$datos['external_product_id']}' no existe en YAuctions.",
            ], 404);
        }

        if ($producto->stock < $datos['quantity']) {
            return response()->json([
                'status'    => 'sin_stock',
                'mensaje'   => 'Stock insuficiente en la tienda japonesa.',
                'disponible' => $producto->stock,
            ], 409);
        }

        $producto->decrement('stock', $datos['quantity']);

        $acuse = 'YAU-' . strtoupper(substr(md5($datos['buyer_reference']), 0, 8));

        return response()->json([
            'status'             => 'vendido',
            'external_reference' => $acuse,
            'product'            => [
                'external_id' => $producto->external_id,
                'sku'         => $producto->sku,
                'nombre'      => $producto->nombre,
            ],
            'quantity'      => $datos['quantity'],
            'buyer_reference' => $datos['buyer_reference'],
            'purchased_at'  => now()->toIso8601String(),
        ]);
    }
}
