<?php

/*
 * Tabla de usuarios. Ademas de lo clasico de login guardo el rol (cliente o
 * administrador) y la plataforma que el usuario prefiere.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la creación de la tabla users.
     * Los métodos de Schema facade abstraen el DDL SQL (CREATE TABLE) según el
     * driver configurado (MySQL / PostgreSQL / SQLite), aportando portabilidad.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // ---------- CLAVE PRIMARIA ----------
            // Identificador surrogate key autoincremental: PK de la entidad.
            $table->id();

            // ---------- ATRIBUTOS DEL DOMINIO ----------
            $table->string('name', 120);
            // Índice UNIQUE: refuerza la integridad de entidad (un correo = una cuenta).
            $table->string('email', 190)->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Hash bcrypt/argon generado por la Capa de Negocio; nunca texto plano.
            $table->string('password');

            // ---------- MULTILATAFORMA  ----------
            // Rol de autorización: separa clientes del personal administrativo (BI).
            $table->enum('rol', ['cliente', 'administrador'])->default('cliente');

            // Tienda externa preferida del usuario; NULL = solo compra local.
            $table->enum('plataforma_preferida', ['local', 'yauctions'])->nullable();

            // ---------- METADATOS DE AUTENTICACIÓN ----------
            $table->rememberToken();
            $table->timestamps(); // created_at y updated_at (auditoría temporal)
        });
    }

    /**
     * Reversión segura de la migración (rollback).
     * Se elimina la tabla completa; las FKs dependientes deben revertirse antes
     * (Laravel invoca los rollbacks en orden inverso de creación).
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
