<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    
    public function run(): void
    {
        User::create([
            'name'                 => 'Ana Torres (Administradora BI)',
            'email'                => 'admin@compralatino.test',
            'password'             => Hash::make('secret1234'),
            'rol'                  => 'administrador',
            'plataforma_preferida' => 'yauctions',
        ]);

        $nombres = [
            'Luis Ramírez', 'María Quispe', 'Carlos Medina', 'Lucía Fernández',
            'Jorge Salas', 'Carmen Rojas', 'Diego Paredes', 'Valeria Nuñez',
        ];

        $clientes = collect($nombres)->map(fn (string $nombre, int $i) => User::create([
            'name'                 => $nombre,
            'email'                => 'cliente' . ($i + 1) . '@compralatino.test',
            'password'             => Hash::make('password'),
            'rol'                  => 'cliente',
            'plataforma_preferida' => $i % 2 === 0 ? 'yauctions' : 'local',
        ]));

        $productos = $this->crearCatalogo();

        $ordenes = $this->simularVentas($clientes, $productos);

        $this->sembrarHistorial($clientes, $productos, $ordenes);

        $this->command?->info('Datos de demostración creados: 9 usuarios, '
            . $productos->count() . ' productos, ' . $ordenes->count() . ' órdenes.');
    }

    private function crearCatalogo(): \Illuminate\Support\Collection
    {
        $definiciones = [
            ['Cámara Fujifilm X-T5',           'electronica', 189000, 'yauctions'],
            ['Teclado mecánico Keychron Q1',   'electronica',  24500, 'yauctions'],
            ['Monitor LG UltraGear 27"',       'electronica',  52000, 'local'],
            ['Audífonos Sony WH-1000XM5',      'electronica',  38900, 'yauctions'],
            ['Figura Gundam RX-78 escala 1/60','coleccionismo', 31500, 'yauctions'],
            ['Funko Pop Naruto Uzumaki',       'coleccionismo',  4200, 'yauctions'],
            ['Pokémon Card Set Scarlet',       'coleccionismo', 12800, 'yauctions'],
            ['Zapatillas Nike Air Max 90',     'moda',          15900, 'local'],
            ['Chaqueta Levi’s Type III',       'moda',          22400, 'yauctions'],
            ['Reloj Casio Vintage digital',    'moda',           8900, 'yauctions'],
            ['Cafetera Chemex 6 tazas',        'hogar',          9800, 'local'],
            ['Set de sartenes T-fal 3 pzas',   'hogar',         14500, 'local'],
            ['Lámpara Muji de mesa',           'hogar',          6400, 'yauctions'],
            ['Libro: El arte de la guerra',     'libros',         1900, 'local'],
            ['Manga One Piece Vol. 105',       'libros',         1200, 'yauctions'],
            ['Bicicleta GW Lynx 29"',          'deportes',     145000, 'local'],
            ['Mancuernas ajustables 24kg',     'deportes',     11200, 'yauctions'],
            ['Tapeta de yoga eco',             'deportes',       3400, 'local'],
        ];

        return collect($definiciones)->map(fn (array $d, int $i) => Product::create([
            'sku'          => 'CL-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            'nombre'       => $d[0],
            'descripcion'  => "Producto de demostración de la categoría {$d[1]}.",
            'precio'       => $d[2],
            'moneda'       => 'JPY',
            'plataforma'   => $d[3],
            'external_id'  => $d[3] === 'yauctions' ? 'YA-' . (100000 + $i) : null,
            'categoria'    => $d[1],
            'stock'        => random_int(15, 80),
            'activo'       => true,
        ]));
    }

    private function simularVentas(\Illuminate\Support\Collection $clientes, \Illuminate\Support\Collection $productos): \Illuminate\Support\Collection
    {
        $ordenes = collect();

        foreach (range(1, 30) as $dia) {
            foreach (range(1, random_int(1, 4)) as $i) {
                $producto = $productos->random();
                $cantidad = random_int(1, 3);
                $cliente  = $clientes->random();
                $fecha    = now()->subDays($dia - 1)->setTime(random_int(8, 20), random_int(0, 59));

                $estado = match (true) {
                    $dia <= 2 && $i === 1    => Order::ESTADO_PENDIENTE,
                    $dia <= 2 && $i === 2    => Order::ESTADO_EN_PROCESO,
                    random_int(1, 100) <= 10 => Order::ESTADO_FALLIDA,
                    default                  => Order::ESTADO_COMPLETADA,
                };

                $orden = Order::create([
                    'user_id'        => $cliente->id,
                    'product_id'     => $producto->id,
                    'cantidad'       => $cantidad,
                    'precio_unitario'=> $producto->precio,
                    'moneda'         => $producto->moneda,
                    'total'          => $producto->precio * $cantidad,
                    'estado'         => $estado,
                    'plataforma'     => $producto->plataforma,
                ]);

                $orden->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();

                if ($estado === Order::ESTADO_COMPLETADA) {
                    $orden->forceFill([
                        'external_reference'   => 'YAU-' . random_int(100000, 999999),
                        'sincronizado_en'      => $fecha->copy()->addMinutes(random_int(2, 30)),
                        'datos_sincronizacion' => [[
                            'fase'     => 'sincronizacion_exitosa',
                            'fecha'    => $fecha->copy()->addMinutes(5)->toIso8601String(),
                            'intentos' => 1,
                            'detalle'  => ['respuesta' => ['status' => 201]],
                        ]],
                    ])->save();
                }

                if ($estado === Order::ESTADO_FALLIDA) {
                    $orden->forceFill([
                        'datos_sincronizacion' => [[
                            'fase'     => 'fallo_comunicacion',
                            'fecha'    => $fecha->copy()->addMinutes(3)->toIso8601String(),
                            'intentos' => 3,
                            'detalle'  => [
                                'mensaje'  => 'YAuctions respondió con el estado HTTP 503.',
                                'contexto' => ['tipo' => 'http_error', 'status' => 503],
                            ],
                        ]],
                    ])->save();
                }

                $ordenes->push($orden);
            }
        }

        return $ordenes;
    }

    private function sembrarHistorial(\Illuminate\Support\Collection $clientes, \Illuminate\Support\Collection $productos, \Illuminate\Support\Collection $ordenes): void
    {
        foreach ($clientes as $cliente) {
            foreach ($productos->random(6) as $producto) {
                UserHistory::registrar($cliente->id, $producto->id, UserHistory::EVENTO_VISTA);
            }

            foreach ($productos->random(2) as $producto) {
                UserHistory::registrar($cliente->id, $producto->id, UserHistory::EVENTO_CARRITO);
            }

            $ordenesCliente = $ordenes->where('user_id', $cliente->id);
            foreach ($ordenesCliente->take(5) as $orden) {
                UserHistory::registrar($cliente->id, $orden->product_id, UserHistory::EVENTO_COMPRA);
            }

            $convertidas = $ordenesCliente->pluck('product_id')->unique()->take(2);

            foreach ($convertidas as $productId) {
                UserHistory::registrar($cliente->id, $productId, UserHistory::EVENTO_RECOMENDACION, [
                    'puntaje' => random_int(6, 10), 'origen' => 'motor_v1', 'convertida' => true,
                ]);
            }

            foreach ($productos->random(3)->pluck('id') as $productId) {
                if (! $convertidas->contains($productId)) {
                    UserHistory::registrar($cliente->id, $productId, UserHistory::EVENTO_RECOMENDACION, [
                        'puntaje' => random_int(1, 5), 'origen' => 'motor_v1', 'convertida' => false,
                    ]);
                }
            }
        }
    }
}
