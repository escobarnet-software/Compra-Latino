<?php

/*
 * Punto de entrada de los seeders. Aca decido el orden en que se ejecutan.
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Registro de seeders a ejecutar en orden determinista.
     */
    public function run(): void
    {
        // Datos de demostración de la Fase III (usuarios, catálogo,
        // órdenes multi-estado y bitácora para el panel de BI).
        $this->call(DemoDataSeeder::class);
    }
}
