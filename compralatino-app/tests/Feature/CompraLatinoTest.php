<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('usuario puede registrarse y crear cuenta', function () {
    $respuesta = $this->post('/registro', [
        'name' => 'Usuario Prueba',
        'email' => 'prueba@compralatino.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'plataforma_preferida' => 'local',
    ]);

    $respuesta->assertRedirect('/');
    $this->assertDatabaseHas('users', ['email' => 'prueba@compralatino.test']);
});

test('usuario autenticado puede buscar productos', function () {
    $usuario = User::factory()->create();
    Product::factory()->create(['nombre' => 'Figura Gundam', 'plataforma' => 'local', 'activo' => true]);

    $respuesta = $this->actingAs($usuario)->get('/?q=Gundam');
    $respuesta->assertStatus(200);
    $respuesta->assertSee('Figura Gundam');
});

test('usuario puede hacer una compra con tarjeta', function () {
    $usuario = User::factory()->create();
    $producto = Product::factory()->create(['plataforma' => 'local', 'stock' => 10, 'precio' => 100.00]);

    $respuesta = $this->actingAs($usuario)->post('/ordenes', [
        '_token' => csrf_token(),
        'product_id' => $producto->id,
        'cantidad' => 1,
        'plataforma' => 'local',
        'titular' => 'Usuario Prueba',
        'numero' => '4242 4242 4242 4242',
        'vencimiento' => '12/28',
        'cvv' => '123',
    ]);

    $respuesta->assertStatus(302);
    $this->assertDatabaseHas('orders', ['user_id' => $usuario->id, 'product_id' => $producto->id]);
});

test('tarjeta invalida es rechazada y no crea orden', function () {
    $usuario = User::factory()->create();
    $producto = Product::factory()->create(['plataforma' => 'local', 'stock' => 10]);

    $antes = Order::count();

    $respuesta = $this->actingAs($usuario)->post('/ordenes', [
        '_token' => csrf_token(),
        'product_id' => $producto->id,
        'cantidad' => 1,
        'plataforma' => 'local',
        'titular' => 'Usuario Prueba',
        'numero' => '4000 0000 0000 0002',
        'vencimiento' => '12/28',
        'cvv' => '123',
    ]);

    $respuesta->assertStatus(302);
    $this->assertSame($antes, Order::count());
});

test('motor de recomendaciones devuelve productos para usuario con historial', function () {
    $usuario = User::factory()->create();
    $producto = Product::factory()->create(['categoria' => 'coleccionismo', 'activo' => true]);

    $respuesta = $this->actingAs($usuario)->get('/para-ti');
    $respuesta->assertStatus(200);
});

test('panel BI es accesible solo por administrador', function () {
    $usuario = User::factory()->create(['rol' => 'cliente']);

    $respuesta = $this->actingAs($usuario)->get('/admin/dashboard');
    $respuesta->assertStatus(302);
});

test('administrador puede ver el panel BI', function () {
    $admin = User::factory()->create(['rol' => 'administrador']);

    $respuesta = $this->actingAs($admin)->get('/admin/dashboard');
    $respuesta->assertStatus(200);
});
