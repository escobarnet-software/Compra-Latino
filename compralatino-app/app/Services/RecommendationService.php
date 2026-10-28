<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserHistory;
use Illuminate\Support\Collection;

class RecommendationService
{
    
    private const PESOS_EVENTO = [
        UserHistory::EVENTO_COMPRA  => 5.0,
        UserHistory::EVENTO_CARRITO => 3.0,
        UserHistory::EVENTO_VISTA   => 1.0,
    ];

    private const PESO_CATEGORIA = 2.0;

    private const LIMITE_DEFECTO = 6;

    public function recomendarPara(User $usuario, ?int $limite = null): Collection
    {
        $limite     = $limite ?? self::LIMITE_DEFECTO;
        $perfil     = $this->construirPerfilDeInteres($usuario);
        $comprados  = $this->idsComprados($usuario);

        if ($perfil->isEmpty()) {
            return $this->productosEnTendencia($limite);
        }

        $categoriasTop = $perfil->keys()->take(3);

        return Product::query()
            ->visibles()
            ->whereIn('categoria', $categoriasTop)
            ->whereNotIn('id', $comprados)
            ->get()
            ->map(function (Product $producto) use ($perfil) {
                $producto->puntaje_recomendacion = round(
                    $producto->interaccionesPopulares() + ($perfil[$producto->categoria] ?? 0) * self::PESO_CATEGORIA,
                    2
                );
                return $producto;
            })
            ->sortByDesc('puntaje_recomendacion')
            ->take($limite)
            ->values();
    }

    public function registrarRecomendaciones(User $usuario, Collection $productos): void
    {
        foreach ($productos as $producto) {
            UserHistory::registrar(
                $usuario->id,
                $producto->id,
                UserHistory::EVENTO_RECOMENDACION,
                ['puntaje' => $producto->puntaje_recomendacion ?? 0, 'origen' => 'motor_v1']
            );
        }
    }

    public function reporteDeEfectividad(): array
    {
        $total = UserHistory::where('evento', UserHistory::EVENTO_RECOMENDACION)->count();

        $conversiones = UserHistory::where('evento', UserHistory::EVENTO_RECOMENDACION)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('orders')
                    ->whereColumn('orders.user_id', 'user_history.user_id')
                    ->whereColumn('orders.product_id', 'user_history.product_id')
                    ->where('orders.estado', Order::ESTADO_COMPLETADA);
            })
            ->count();

        return [
            'total_recomendaciones' => $total,
            'conversiones'         => $conversiones,
            'tasa_conversion_pct'  => $total > 0 ? round(($conversiones / $total) * 100, 2) : 0.0,
        ];
    }

    private function construirPerfilDeInteres(User $usuario): Collection
    {
        return UserHistory::query()
            ->where('user_id', $usuario->id)
            ->whereIn('evento', array_keys(self::PESOS_EVENTO))
            ->with('product:id,categoria')
            ->get()
            ->filter(fn ($evento) => $evento->product !== null)
            ->groupBy(fn ($evento) => $evento->product->categoria)
            ->map(function ($eventos) {
                return round($eventos->sum(
                    fn ($evento) => self::PESOS_EVENTO[$evento->evento] ?? 0.0
                ), 2);
            })
            ->sortDesc();
    }

    private function idsComprados(User $usuario): array
    {
        return $usuario->orders()
            ->completadas()
            ->pluck('product_id')
            ->unique()
            ->all();
    }

    private function productosEnTendencia(int $limite): Collection
    {
        $populares = UserHistory::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('product_id, COUNT(*) as interacciones')
            ->groupBy('product_id')
            ->orderByDesc('interacciones')
            ->limit($limite)
            ->pluck('product_id');

        return Product::query()
            ->visibles()
            ->whereIn('id', $populares)
            ->get()
            ->sortByDesc(fn ($producto) => $populares->search($producto->id))
            ->values();
    }
}
