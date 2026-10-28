
<!DOCTYPE html>

<html lang="es">
<head>
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Inteligencia de Negocios · CompraLatino</title>

    <style>
           Colores y medidas del sistema, en un solo lugar.
           Si quiero cambiar la marca, toco aqui y afecta todo.
        :root {
            --color-primario: #1e3a8a;
            --color-primario-suave: #3b82f6;
            --color-exito: #15803d;
            --color-alerta: #b91c1c;
            --color-fondo: #f1f5f9;
            --color-tarjeta: #ffffff;
            --color-texto: #0f172a;
            --color-texto-suave: #64748b;
            --radio: 12px;
            --sombra: 0 1px 3px rgba(15, 23, 42, .12);
        }

        /* Reset ligero: consistencia entre navegadores. */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6; /* interlineado óptimo (usabilidad lectora) */
        }

        /* Skip-link: mejora accesibilidad por teclado; invisible hasta el foco. */
        .skip-link {
            position: absolute; left: -9999px;
            background: var(--color-primario); color: #fff; padding: .75rem 1rem;
        }
        .skip-link:focus { left: 0; top: 0; z-index: 100; }

        /* ---------- BARRA SUPERIOR ---------- */
        .app-header {
            background: var(--color-primario); color: #fff;
            padding: 1rem 1.25rem;
            display: flex; flex-wrap: wrap; /* se apila en móvil */
            align-items: center; justify-content: space-between; gap: .75rem;
        }
        .app-header h1 { font-size: 1.15rem; font-weight: 600; }

        /* ---------- LAYOUT PRINCIPAL (mobile-first) ---------- */
        .app-layout { display: block; }
        .app-nav {
            background: var(--color-tarjeta); padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .app-nav ul { list-style: none; display: flex; flex-direction: column; gap: .5rem; }
        .app-nav a {
            display: block; padding: .65rem .9rem; border-radius: 8px;
            color: var(--color-primario); text-decoration: none; font-weight: 500;
            min-height: 44px; /* tamaño táctil mínimo WCAG */
        }
        .app-nav a:hover, .app-nav a[aria-current="page"] { background: #e0e7ff; }
        .app-main { padding: 1.25rem; }

        /* ---------- TARJETAS KPI (Grid responsive) ---------- */
        /*
         * CSS Grid con minmax() y auto-fill: la cantidad de columnas se adapta
         * AUTOMÁTICAMENTE al ancho del dispositivo:
         *   móvil → 1 columna · tablet → 2 · escritorio → 4
         */
        .kpi-grid {
            display: grid; gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            margin-bottom: 1.5rem;
        }
        .kpi-card {
            background: var(--color-tarjeta); border-radius: var(--radio);
            box-shadow: var(--sombra); padding: 1.25rem;
            border-left: 5px solid var(--color-primario-suave);
        }
        .kpi-card.exito  { border-left-color: var(--color-exito); }
        .kpi-card.alerta { border-left-color: var(--color-alerta); }
        .kpi-card .etiqueta {
            font-size: .8rem; text-transform: uppercase; letter-spacing: .04em;
            color: var(--color-texto-suave); font-weight: 600;
        }
        .kpi-card .valor { font-size: 1.75rem; font-weight: 700; margin-top: .25rem; }

        /* ---------- SECCIONES Y TABLAS ---------- */
        .panel {
            background: var(--color-tarjeta); border-radius: var(--radio);
            box-shadow: var(--sombra); padding: 1.25rem; margin-bottom: 1.5rem;
        }
        .panel h2 { font-size: 1.05rem; margin-bottom: .9rem; color: var(--color-primario); }

        /* Scroll horizontal de tablas en móvil (evita desbordes). */
        .tabla-contenedor { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        caption { text-align: left; color: var(--color-texto-suave); padding-bottom: .5rem; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; white-space: nowrap; }

        /* ---------- GRÁFICO DE BARRAS (CSS puro, sin dependencias JS) ---------- */
        .barras { display: flex; align-items: flex-end; gap: .4rem; height: 160px; }
        .barra {
            flex: 1; min-width: 10px;
            background: linear-gradient(180deg, var(--color-primario-suave), var(--color-primario));
            border-radius: 4px 4px 0 0; position: relative;
        }
        .barra span {
            position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            font-size: .7rem; color: var(--color-texto-suave); padding-bottom: 2px;
        }

        /* ---------- BREAKPOINTS (responsive) ---------- */
        /* TABLET: navegación a la izquierda (2 columnas). */
        @media (min-width: 640px) {
            .app-layout { display: grid; grid-template-columns: 220px 1fr; }
            .app-nav { border-right: 1px solid #e2e8f0; border-bottom: none; }
            .app-main { padding: 1.75rem; }
        }
        /* ESCRITORIO: KPIs en 4 columnas; más aire vertical. */
        @media (min-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(4, 1fr); }
            .app-main { padding: 2.25rem; }
        }
    </style>
</head>
<body>
    
    <a class="skip-link" href="#contenido">Saltar al contenido principal</a>

    <header class="app-header">
        <h1>CompraLatino · Panel de Inteligencia de Negocios</h1>
        
        <p>Hola, {{ auth()->user()->name ?? 'Administrador' }}</p>
    </header>

    <div class="app-layout">
        
        <nav class="app-nav" aria-label="Navegación administrativa">
            <ul>
                <li><a href="{{ route('bi.dashboard') }}" aria-current="page">Panel BI</a></li>
                <li><a href="{{ route('bi.auditoria') }}">Auditoría de fallos</a></li>
                <li><a href="{{ route('store.index') }}">Volver a la tienda</a></li>
            </ul>
        </nav>

        <main class="app-main" id="contenido">

            @if (session('status'))
                <p role="status" class="panel">{{ session('status') }}</p>
            @endif

            <section class="kpi-grid" aria-label="Indicadores clave de rendimiento">
                
                <article class="kpi-card exito">
                    <p class="etiqueta">Ingresos (30 días)</p>
                    <p class="valor">{{ number_format((float) ($resumenVentas->ingresos ?? 0), 2) }}</p>
                </article>

                <article class="kpi-card">
                    <p class="etiqueta">Órdenes completadas</p>
                    <p class="valor">{{ $resumenVentas->ordenes ?? 0 }}</p>
                </article>

                <article class="kpi-card">
                    <p class="etiqueta">Ticket promedio</p>
                    <p class="valor">{{ number_format((float) $ticketPromedio, 2) }}</p>
                </article>

                <article class="kpi-card exito">
                    <p class="etiqueta">Conversión recomendaciones</p>
                    <p class="valor">{{ $tasaConversionRecomendaciones }}%</p>
                </article>

                <article class="kpi-card {{ $fallasSincronizacion > 0 ? 'alerta' : '' }}">
                    <p class="etiqueta">Fallos de sincronización</p>
                    <p class="valor">{{ $fallasSincronizacion }}</p>
                </article>

                <article class="kpi-card">
                    <p class="etiqueta">Usuarios registrados</p>
                    <p class="valor">{{ $totalUsuarios }}</p>
                </article>

                <article class="kpi-card">
                    <p class="etiqueta">Productos activos</p>
                    <p class="valor">{{ $productosActivos }}</p>
                </article>

                <article class="kpi-card">
                    <p class="etiqueta">En cola de sincronización</p>
                    <p class="valor">
                        {{ ($ordenesPorEstado['pendiente'] ?? 0) + ($ordenesPorEstado['en_proceso'] ?? 0) }}
                    </p>
                </article>
            </section>

            <section class="panel" aria-labelledby="titulo-ventas-dia">
                <h2 id="titulo-ventas-dia">Ingresos diarios (últimos 30 días)</h2>
                @php
                    // Máximo para escalar cada barra al 100% del contenedor.
                    $maxIngresoDia = max(1, (float) $ventasPorDia->max('ingresos'));
                @endphp
                <div class="barras" role="img"
                     aria-label="Gráfico de barras de ingresos diarios de los últimos 30 días">
                    @forelse ($ventasPorDia as $dia)
                        @php $altura = ((float) $dia->ingresos / $maxIngresoDia) * 100; @endphp
                        <div class="barra" style="height: {{ round($altura) }}%">
                            <span>{{ number_format((float) $dia->ingresos, 0) }}</span>
                        </div>
                    @empty
                        <p>Sin ventas registradas en el periodo.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel" aria-labelledby="titulo-plataformas">
                <h2 id="titulo-plataformas">Rendimiento por plataforma</h2>
                <div class="tabla-contenedor">
                    <table>
                        <caption>Ventas efectivas comparadas por tienda (últimos 30 días)</caption>
                        <thead>
                            <tr>
                                <th scope="col">Plataforma</th>
                                <th scope="col">Órdenes</th>
                                <th scope="col">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ventasPorPlataforma as $fila)
                                <tr>
                                    <td>{{ ucfirst($fila->plataforma) }}</td>
                                    <td>{{ $fila->ordenes }}</td>
                                    <td>{{ number_format((float) $fila->ingresos, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3">Sin datos disponibles.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" aria-labelledby="titulo-top">
                <h2 id="titulo-top">Top 5 productos por ingreso</h2>
                <div class="tabla-contenedor">
                    <table>
                        <caption>Productos más rentables del periodo</caption>
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Producto</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Unidades</th>
                                <th scope="col">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topProductos as $indice => $producto)
                                <tr>
                                    <td>{{ $indice + 1 }}</td>
                                    
                                    <td>{{ $producto->nombre }}</td>
                                    <td>{{ $producto->categoria }}</td>
                                    <td>{{ $producto->unidades }}</td>
                                    <td>{{ number_format((float) $producto->ingresos, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">Aún no hay ventas para clasificar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" aria-labelledby="titulo-ia">
                <h2 id="titulo-ia">Motor de recomendaciones · Efectividad</h2>
                <div class="tabla-contenedor">
                    <table>
                        <caption>Conversión de sugerencias del motor en compras reales</caption>
                        <thead>
                            <tr>
                                <th scope="col">Recomendaciones entregadas</th>
                                <th scope="col">Convertidas en compra</th>
                                <th scope="col">Tasa de conversión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $efectividadRecomendaciones['total_recomendaciones'] }}</td>
                                <td>{{ $efectividadRecomendaciones['conversiones'] }}</td>
                                <td>{{ $efectividadRecomendaciones['tasa_conversion_pct'] }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <footer class="app-header">
        <p>CompraLatino — Fase III · Arquitectura por capas · Datos al: {{ now()->format('d/m/Y H:i') }}</p>
    </footer>
</body>
</html>
