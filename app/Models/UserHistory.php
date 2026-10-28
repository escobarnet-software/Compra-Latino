<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHistory extends Model
{
    
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

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
