<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'plataforma_preferida',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(UserHistory::class);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function plataformaPorDefecto(): string
    {
        return $this->plataforma_preferida ?? 'local';
    }
}
