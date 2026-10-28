<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->char('moneda', 3)->default('JPY');
            $table->decimal('total', 12, 2);

            $table->enum('estado', [
                'pendiente',
                'en_proceso',
                'completada',
                'fallida',
            ])->default('pendiente')->index();

            $table->enum('plataforma', ['local', 'yauctions'])->default('yauctions');
            $table->string('external_reference', 120)->nullable();
            $table->json('datos_sincronizacion')->nullable();
            $table->timestamp('sincronizado_en')->nullable();

            $table->timestamps();

            $table->index(['estado', 'created_at'], 'idx_orders_estado_fecha');
            $table->index(['plataforma', 'created_at'], 'idx_orders_plataforma_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
