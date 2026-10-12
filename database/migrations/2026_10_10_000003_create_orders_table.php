<?php

/*
 * Tabla de ordenes: el corazon del e-commerce.
 * Apunta al usuario y al producto con claves foraneas. Preferi RESTRICT sobre
 * CASCADE: si algo se intenta borrar mal, que la base lo frene antes que
 * perder el historial de compras. Guarda el estado, el total y los datos del
 * pago y de la sincronizacion.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // ---- INTEGRIDAD REFERENCIAL (FK) ----
            // Restringido: no se puede borrar la raíz mientras existan hijos.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            // ---- LÍNEA ECONÓMICA DE LA ORDEN ----
            $table->unsignedInteger('cantidad');
            // Se congela el precio al momento de comprar: si el catálogo cambia
            // después, la orden conserva su valor original (auditabilidad).
            $table->decimal('precio_unitario', 10, 2);
            $table->char('moneda', 3)->default('JPY');
            $table->decimal('total', 12, 2);

            // ---- MÁQUINA DE ESTADOS ----
            $table->enum('estado', [
                'pendiente',   // creada, aún no procesada por el Job
                'en_proceso',  // Job en ejecución, llamada a YAuctions en curso
                'completada',  // sincronizada exitosamente con la tienda externa
                'fallida',     // error de comunicación o de regla de negocio
            ])->default('pendiente')->index();

            // ---- AUDITORÍA DE SINCRONIZACIÓN  ----
            $table->enum('plataforma', ['local', 'yauctions'])->default('yauctions');
            $table->string('external_reference', 120)->nullable();
            // JSON: flexible para guardar payloads arbitrarios de la API REST.
            $table->json('datos_sincronizacion')->nullable();
            $table->timestamp('sincronizado_en')->nullable();

            $table->timestamps();

            // ---- ÍNDICES COMPUESTOS (rendimiento + analítica BI) ----
            // Aceleran los consultorios del panel BI: "ventas por estado",
            // "ventas por plataforma en un periodo", etc.
            $table->index(['estado', 'created_at'], 'idx_orders_estado_fecha');
            $table->index(['plataforma', 'created_at'], 'idx_orders_plataforma_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
