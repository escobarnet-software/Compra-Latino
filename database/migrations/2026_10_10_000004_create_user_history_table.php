<?php

/*
 * Tabla de historial de usuario (la bitacora).
 * Un evento por fila: que producto, de que tipo (vista, carrito, compra o
 * recomendacion) y un contexto en JSON. Es la materia prima del recomendador.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_history', function (Blueprint $table) {
            $table->id();

            // FKs con cascade: el historial es un DERIVADO del usuario/producto.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Clasificación del evento: dominio cerrado controlado.
            $table->enum('evento', [
                'vista',         // el usuario visitó la ficha del producto
                'carrito',       // lo agregó al carrito (intención de compra)
                'compra',        // compró el producto (señal fuerte de preferencia)
                'recomendacion', // el motor le recomendó este producto (para medir conversión)
            ])->index();

            // Metadatos flexibles del evento (dispositivo, origen, puntaje, etc.).
            $table->json('contexto')->nullable();

            $table->timestamps();

            // ---- Consultas típicas soportadas por estos índices ----
            // • Recomendador: WHERE user_id = ? ORDER BY created_at DESC
            // • BI:          WHERE evento = 'compra' AND created_at BETWEEN ...
            $table->index(['user_id', 'evento'], 'idx_history_user_evento');
            $table->index(['evento', 'created_at'], 'idx_history_evento_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_history');
    }
};
