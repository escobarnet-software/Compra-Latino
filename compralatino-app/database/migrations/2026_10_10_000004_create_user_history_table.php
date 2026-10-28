<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->enum('evento', [
                'vista',
                'carrito',
                'compra',
                'recomendacion',
            ])->index();

            $table->json('contexto')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'evento'], 'idx_history_user_evento');
            $table->index(['evento', 'created_at'], 'idx_history_evento_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_history');
    }
};
