<?php

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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(UserHistory::class);
    }

    public function scopeVisibles($query)
    {
        return $query->where('activo', true);
    }

    public function hayStockDisponible(int $cantidad = 1): bool
    {
        return $this->activo && $this->stock >= $cantidad;
    }

    public function precioFormateado(): string
    {
        return number_format((float) $this->precio, 2) . ' ' . $this->moneda;
    }

    public function interaccionesPopulares(int $dias = 30): float
    {
        return (float) $this->history()
            ->where('created_at', '>=', now()->subDays($dias))
            ->count();
    }
}
