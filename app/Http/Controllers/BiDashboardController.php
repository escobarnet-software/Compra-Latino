<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\View\View;

class BiDashboardController extends Controller
{
    
    public function index(RecommendationService $recomendador): View
    {
        $desde = now()->subDays(30)->startOfDay();

        $resumenVentas = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->selectRaw('COUNT(*) as ordenes, COALESCE(SUM(total), 0) as ingresos')
            ->first();

        $ticketPromedio = $resumenVentas->ordenes > 0
            ? round($resumenVentas->ingresos / $resumenVentas->ordenes, 2)
            : 0;

        $ordenesPorEstado = Order::where('orders.created_at', '>=', $desde)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $ventasPorPlataforma = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->selectRaw('plataforma, COUNT(*) as ordenes, SUM(total) as ingresos')
            ->groupBy('plataforma')
            ->get();

        $ventasPorDia = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->selectRaw("DATE(orders.created_at) as dia, SUM(orders.total) as ingresos, COUNT(*) as ordenes")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $topProductos = Order::completadas()
            ->where('orders.created_at', '>=', $desde)
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->selectRaw('products.nombre, products.categoria, SUM(orders.cantidad) as unidades, SUM(orders.total) as ingresos')
            ->groupBy('products.nombre', 'products.categoria')
            ->orderByDesc('ingresos')
            ->limit(5)
            ->get();

        $efectividadRecomendaciones    = $recomendador->reporteDeEfectividad();
        $tasaConversionRecomendaciones = $efectividadRecomendaciones['tasa_conversion_pct'];

        $fallasSincronizacion = Order::where('estado', Order::ESTADO_FALLIDA)
            ->where('orders.created_at', '>=', $desde)
            ->count();

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

    public function auditoria(): View
    {
        $ordenesFallidas = Order::where('estado', Order::ESTADO_FALLIDA)
            ->with(['user:id,name,email', 'product:id,nombre,sku'])
            ->latest()
            ->paginate(20);

        return view('bi.auditoria', compact('ordenesFallidas'));
    }
}
