<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'cantidad',
        'precio_unitario',
        'moneda',
        'total',
        'estado',
        'plataforma',
        'marca_tarjeta',
        'tarjeta_ultimos4',
        'referencia_pago',
        'pagado_en',
        'external_reference',
        'datos_sincronizacion',
        'sincronizado_en',
    ];

    protected $casts = [
        'precio_unitario'      => 'decimal:2',
        'total'                => 'decimal:2',
        'datos_sincronizacion' => 'array',
        'sincronizado_en'      => 'datetime',
    ];

    protected $hidden = [
        'datos_sincronizacion',
    ];

    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_FALLIDA    = 'fallida';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    public function scopeDelPeriodo(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    public function estaSincronizada(): bool
    {
        return $this->estado === self::ESTADO_COMPLETADA
            && ! empty($this->external_reference);
    }

    public function puedeSincronizarse(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO, self::ESTADO_FALLIDA], true);
    }

    public function registrarAuditoria(string $fase, array $datos, int $intentos = 1): void
    {
        $auditoria           = $this->datos_sincronizacion ?? [];
        $auditoria[]         = [
            'fase'     => $fase,
            'fecha'    => now()->toIso8601String(),
            'intentos' => $intentos,
            'detalle'  => $datos,
        ];

        $this->forceFill(['datos_sincronizacion' => $auditoria])->save();
    }
}
