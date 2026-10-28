<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('sku', 64)->unique();
            $table->string('nombre', 180);
            $table->text('descripcion')->nullable();

            $table->decimal('precio', 10, 2);
            $table->char('moneda', 3)->default('JPY');

            $table->enum('plataforma', ['local', 'yauctions'])->default('local');
            $table->string('external_id', 64)->nullable()->index();

            $table->string('categoria', 80)->index();

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
