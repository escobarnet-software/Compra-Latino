<?php

/*
 * Modelo User: el usuario de la plataforma.
 * Ademas del login, relaciona al usuario con sus ordenes y con su historial de
 * interacciones, que es lo que usa el recomendador. La contrasena nunca se
 * expone cuando se serializa el modelo.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Solo estos atributos admiten asignación masiva (protección ORM). */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'plataforma_preferida',
    ];

    /** Contraseña jamás visible en vistas ni respuestas JSON. */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // RELACIONES (integridad referencial proyectada al dominio)

    /** Un usuario acumula MUCHAS órdenes de compra. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Un usuario genera MUCHOS eventos de comportamiento (bitácora BI). */
    public function history(): HasMany
    {
        return $this->hasMany(UserHistory::class);
    }

    // MÉTODOS DE DOMINIO

    /**
     * Regla de autorización: ¿el usuario pertenece al área administrativa?
     * Usada por el middleware 'can:administrador' que protege el panel BI,
     * demostrando separación de capas: el CONTROLADOR no valida permisos a mano.
     */
    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    /**
     * Tienda externa por defecto para nuevas compras (multiplataforma).
     */
    public function plataformaPorDefecto(): string
    {
        return $this->plataforma_preferida ?? 'local';
    }
}
