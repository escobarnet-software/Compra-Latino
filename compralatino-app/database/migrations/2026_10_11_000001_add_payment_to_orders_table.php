<?php

/*
 * Agrego a la tabla de ordenes los datos del cobro con tarjeta: marca, ultimos
 * 4 digitos, referencia del pago y fecha. Nunca guardo el numero completo ni
 * el CVV.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Marca detectada de la tarjeta (visa | mastercard | amex | desconocida).
            $table->string('marca_tarjeta', 20)->nullable()->after('plataforma');

            // Últimos 4 dígitos: identificación visual sin exponer el PAN.
            $table->char('tarjeta_ultimos4', 4)->nullable()->after('marca_tarjeta');

            // Referencia del cargo aprobada por la pasarela (trazabilidad).
            $table->string('referencia_pago', 120)->nullable()->after('tarjeta_ultimos4');

            // Momento en que el cobro quedó confirmado (auditoría financiera).
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
