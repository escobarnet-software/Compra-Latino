<?php

/*
 * Modelo Product: un producto del catalogo.
 * Sirve tanto para productos propios como para los que vienen de YAuctions
 * (estos ultimos se identifican con external_id). Desde aca se controla que
 * solo se listen los activos y si hay stock disponible.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'nombre',
        'descripcion',
        'precio',
        'moneda',
        'plataforma',
        'external_id',
        'categoria',
        'stock',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock'  => 'integer',
        'activo' => 'boolean',
    ];

    // RELACIONES

    /** Un producto puede ser comprado en MUCHAS órdenes. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Un producto genera MUCHOS eventos en la bitácora de usuarios. */
    public function history(): HasMany
    {
        return $this->hasMany(UserHistory::class);
    }

    // SCOPES Y MÉTODOS DE DOMINIO

    /**
     * Catálogo visible: solo productos activos. Centraliza la regla "lo no
     * publicado no se muestra" en el modelo (fuente única de verdad).
     */
    public function scopeVisibles($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Regla de inventario: un producto es comprable si tiene stock y está activo.
     * El Job de compra invocará esta validación ANTES de llamar a YAuctions.
     */
    public function hayStockDisponible(int $cantidad = 1): bool
    {
        return $this->activo && $this->stock >= $cantidad;
    }

    /**
     * Precio formateado para presentación (la capa de presentación delega aquí
     * el formato del dominio monetario, evitando duplicar lógica en vistas).
     */
    public function precioFormateado(): string
    {
        return number_format((float) $this->precio, 2) . ' ' . $this->moneda;
    }

    /**
     * Fuerza de popularidad del producto en la bitácora (reciente).
     * Utilizado por RecommendationService para puntuar candidatos: un producto
     * con muchas interacciones de la comunidad es una sugerencia más segura
     * (filtrado colaborativo basado en popularidad).
     */
    public function interaccionesPopulares(int $dias = 30): float
    {
        return (float) $this->history()
            ->where('created_at', '>=', now()->subDays($dias))
            ->count();
    }
}
