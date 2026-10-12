<?php

/*
 * Modelo UserHistory: la bitacora de lo que hace cada usuario en la tienda
 * (que vio, que compro, que se le recomendo). De aqui saca los datos el motor
 * de recomendaciones y el panel de BI.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHistory extends Model
{
    /** Eventos admitidos (dominio cerrado — evita basura en la bitácora). */
    public const EVENTO_VISTA         = 'vista';
    public const EVENTO_CARRITO       = 'carrito';
    public const EVENTO_COMPRA        = 'compra';
    public const EVENTO_RECOMENDACION = 'recomendacion';

    protected $table = 'user_history';

    protected $fillable = [
        'user_id',
        'product_id',
        'evento',
        'contexto',
    ];

    protected $casts = [
        'contexto' => 'array',
    ];

    // RELACIONES

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // FÁBRICA DE EVENTOS (punto único de registro)

    /**
     * Persiste un evento de comportamiento. Usado por StoreController y por
     * el motor de recomendaciones para trazar cada recomendación entregada
     * (permite medir su tasa de conversión en el panel BI).
     */
    public static function registrar(int $userId, int $productId, string $evento, array $contexto = []): self
    {
        return self::create([
            'user_id'   => $userId,
            'product_id'=> $productId,
            'evento'    => $evento,
            'contexto'  => $contexto,
        ]);
    }
}
