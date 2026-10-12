<?php

/*
 * Tabla de productos. Guarda precio, stock y categoria. Para los productos que
 * vienen de YAuctions se guarda el external_id para saber cual es alla.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Clave de negocio única del producto (1FN: sin repetidos).
            $table->string('sku', 64)->unique();
            $table->string('nombre', 180);
            $table->text('descripcion')->nullable();

            // ---- Dominio monetario: precisión exacta y moneda explícita ----
            $table->decimal('precio', 10, 2);
            $table->char('moneda', 3)->default('JPY');

            // ---- Proveniencia multiplataforma  ----
            $table->enum('plataforma', ['local', 'yauctions'])->default('local');
            // ID del producto en la tienda externa; NULL cuando es inventario local.
            $table->string('external_id', 64)->nullable()->index();

            // Categoría usada por el MOTOR DE RECOMENDACIONES (interés del usuario).
            $table->string('categoria', 80)->index();

            // Control de inventario y publicación.
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
