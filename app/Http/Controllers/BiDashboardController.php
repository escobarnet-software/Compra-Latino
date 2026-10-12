<?php

/*
 * BiDashboardController: arma los datos del panel de Inteligencia de Negocios.
 * Calcula los KPIs (ingresos, ticket promedio, ventas por plataforma, cuan
 * efectivo es el recomendador y los fallos de sincronizacion) y se los pasa a
 * la vista. Es de solo lectura y esta protegido para administradores.
 */

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\View\View;

class BiDashboardController extends Controller
{
    /**
     * PANEL PRINCIPAL DE BI: consolida los KPIs del negocio.
     *
     * Calcula las "medidas" agregadas y las inyecta en la vista Blade
     * 'dashboard-bi'. La vista NO consulta la BD directamente
     * (los datos van del modelo al controlador y a la vista: Model → Controller → View).
     */
    public function index(RecommendationService $recomendador): View
    {
        // ---- VENTANA TEMPORAL: últimos 30 días (parámetro típico de BI) ----
        $desde = now()->subDays(30)->startOfDay();

        // ---- MEDIDA 1: ingresos y volumen de ventas efectivas ----
        // NOTA SQL: se califica 'orders.created_at' con el nombre de tabla porque
        // la consulta de topProductos hace JOIN con 'products' (ambas tablas tienen
        // 'created_at' y SQLite/MySQL exigen desambiguar la columna).
        $resumenVentas = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->selectRaw('COUNT(*) as ordenes, COALESCE(SUM(total), 0) as ingresos')
            ->first();

        // ---- MEDIDA 2: ticket promedio (indicador de decisión comercial) ----
        $ticketPromedio = $resumenVentas->ordenes > 0
            ? round($resumenVentas->ingresos / $resumenVentas->ordenes, 2)
            : 0;

        // ---- MEDIDA 3: distribución de órdenes por estado ----
        // Permite al administrador detectar cuellos de botella en la cola
        // (ej. muchas órdenes 'fallida' ⇒ problemas con la API de YAuctions).
        $ordenesPorEstado = Order::where('orders.created_at', '>=', $desde)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // ---- DIMENSIÓN: ventas por plataforma (comparativa multiplataforma) ----
        $ventasPorPlataforma = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->selectRaw('plataforma, COUNT(*) as ordenes, SUM(total) as ingresos')
            ->groupBy('plataforma')
            ->get();

        // ---- SERIE TEMPORAL: ingresos diarios (alimenta el gráfico) ----
        $ventasPorDia = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->selectRaw("DATE(orders.created_at) as dia, SUM(orders.total) as ingresos, COUNT(*) as ordenes")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // ---- DIMENSIÓN PRODUCTO: top 5 más vendidos ----
        $topProductos = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->selectRaw('products.nombre, products.categoria, SUM(orders.cantidad) as unidades, SUM(orders.total) as ingresos')
            ->groupBy('products.nombre', 'products.categoria')
            ->orderByDesc('ingresos')
            ->limit(5)
            ->get();

        // ---- IA / KPI del MOTOR DE RECOMENDACIONES  ----
        // Calculado por la Capa de Negocio; el controlador solo lo transporta.
        $efectividadRecomendaciones    = $recomendador->reporteDeEfectividad();
        $tasaConversionRecomendaciones = $efectividadRecomendaciones['tasa_conversion_pct'];

        // ---- SALUD DE LA INTEGRACIÓN: auditorías de fallo de sincronización ----
        $fallasSincronizacion = Order::where('estado', Order::ESTADO_FALLIDA)
            ->where('orders.created_at', '>=', $desde)
            ->count();

        // ---- INDICADORES DE BASE (usuarios, catálogo activo) ----
        $totalUsuarios    = User::count();
        $productosActivos = Product::visibles()->count();

        return view('dashboard-bi', compact(
            'resumenVentas',
            'ticketPromedio',
            'ordenesPorEstado',
            'ventasPorPlataforma',
            'ventasPorDia',
            'topProductos',
            'efectividadRecomendaciones',
            'tasaConversionRecomendaciones',
            'fallasSincronizacion',
            'totalUsuarios',
            'productosActivos'
        ));
    }

    /**
     * BITÁCORA DE AUDITORÍA: lista las órdenes fallidas con su JSON de
     * auditoría, permitiendo al administrador TOMAR DECISIONES (reintentar,
     * contactar al proveedor japonés, reembolsar). Es el "Data Warehouse
     * ligero" del monolito: consultas puntuales sobre la BD transaccional.
     */
    public function auditoria(): View
    {
        $ordenesFallidas = Order::where('estado', Order::ESTADO_FALLIDA)
            ->with(['user:id,name,email', 'product:id,nombre,sku'])
            ->latest()
            ->paginate(20);

        return view('bi.auditoria', compact('ordenesFallidas'));
    }
}
