<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('marca_tarjeta', 20)->nullable()->after('plataforma');

            $table->char('tarjeta_ultimos4', 4)->nullable()->after('marca_tarjeta');

            $table->string('referencia_pago', 120)->nullable()->after('tarjeta_ultimos4');

            $table->timestamp('pagado_en')->nullable()->after('referencia_pago');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['marca_tarjeta', 'tarjeta_ultimos4', 'referencia_pago', 'pagado_en']);
        });
    }
};
