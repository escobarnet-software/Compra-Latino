<?php

/*
 * Modelo Order: una orden de compra.
 * Guarda quien compro, que producto, el total y los datos no sensibles del
 * pago con tarjeta. Tambien controla el ciclo de vida de la orden (pendiente,
 * en proceso, completada o fallida) y va acumulando la auditoria de cada
 * intento de sincronizacion con YAuctions.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    /**
     * WHITELIST de asignación masiva: cualquier clave fuera de esta lista es
     * descartada por Eloquent al usar Order::create($request->validated()),
     * cerrando la que manden campos de mas desde el formulario.
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'cantidad',
        'precio_unitario',
        'moneda',
        'total',
        'estado',
        'plataforma',
        // Datos NO sensibles del cobro (nunca el PAN ni el CVV).
        'marca_tarjeta',
        'tarjeta_ultimos4',
        'referencia_pago',
        'pagado_en',
        'external_reference',
        'datos_sincronizacion',
        'sincronizado_en',
    ];

    /** CASTS: convierten tipos de BD en tipos PHP de dominio. */
    protected $casts = [
        'precio_unitario'      => 'decimal:2',
        'total'                => 'decimal:2',
        'datos_sincronizacion' => 'array', // auditoría JSON ↔ array PHP
        'sincronizado_en'      => 'datetime',
    ];

    /** Nunca exponer la auditoría interna en respuestas serializadas. */
    protected $hidden = [
        'datos_sincronizacion',
    ];

    // ---- CONSTANTES DE DOMINIO ( sin "strings mágicos") ----

    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_FALLIDA    = 'fallida';

    // ---- RELACIONES (mapeo FK → grafo de objetos) ----

    /** Muchas órdenes pertenecen a UN usuario (FK: orders.user_id). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Muchas órdenes referencian UN producto (FK: orders.product_id). */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ---- SCOPES (DSL de Eloquent; sustituyen SQL crudo) ----

    /** Filtra ventas efectivas; base de los KPIs del panel BI. */
    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    /** Rango temporal: sustenta los cuadros de mando mensuales de BI. */
    public function scopeDelPeriodo(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    // ---- MÉTODOS DE DOMINIO (comportamiento, no solo datos) ----

    /** ¿La orden tiene acuse confirmado por la tienda externa? */
    public function estaSincronizada(): bool
    {
        return $this->estado === self::ESTADO_COMPLETADA
            && ! empty($this->external_reference);
    }

    /**
     * Regla: 'pendiente' inicia el proceso; 'en_proceso' permite CONTINUARLO
     * (los reintentos del worker de Laravel re-ejecutan handle() sobre la
     * misma orden: si 'en_proceso' no fuera sincronizable, el propio reintento
     * se rechazaría a sí mismo). Solo 'completada' es terminal e inmutable
     * (evita dobles cargos); 'fallida' también puede reintentarse.
     */
    public function puedeSincronizarse(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_EN_PROCESO, self::ESTADO_FALLIDA], true);
    }

    /**
     * Registra la auditoría de la comunicación con la API REST .
     * Centraliza la escritura del JSON de auditoría en UN solo punto:
     * todo intento de sincronización —exitoso o fallido— queda trazable para
     * el panel de BI y para la toma de decisiones administrativas.
     */
    public function registrarAuditoria(string $fase, array $datos, int $intentos = 1): void
    {
        $auditoria           = $this->datos_sincronizacion ?? [];
        $auditoria[]         = [
            'fase'     => $fase,
            'fecha'    => now()->toIso8601String(),
            'intentos' => $intentos,
            'detalle'  => $datos,
        ];

        // forceFill + save: escritura controlada desde la Capa de Negocio.
        $this->forceFill(['datos_sincronizacion' => $auditoria])->save();
    }
}
