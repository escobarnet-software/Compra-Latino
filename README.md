# 🛒 CompraLatino — Fase 3

E-commerce intermediario (Laravel) que conecta con la tienda japonesa **YAuctions**,
con **motor de recomendaciones** basado en el historial del usuario y un
**Dashboard de Inteligencia de Negocios (BI)** para el área administrativa.

---

## 📋 Requisitos previos

- **PHP 8.3** o superior
- **Composer**
- La extensión `pdo_sqlite` habilitada (viene por defecto en la mayoría de instalaciones de PHP)

No necesitas MySQL ni Redis: el proyecto usa **SQLite** y la cola en base de datos.

---

## 🚀 Pasos para encender el proyecto

Todo se ejecuta dentro de la carpeta `compralatino-app/`.

```bash
# 1. Entrar a la carpeta de la aplicación
cd compralatino-app

# 2. Instalar dependencias de PHP
composer install

# 3. Crear el archivo de configuración .env
copy .env.example .env          # En Windows
# cp .env.example .env          # En Linux/Mac

# 4. Generar la clave de la aplicación
php artisan key:generate

# 5. Crear la base de datos SQLite (archivo vacío)
#    En Windows PowerShell:
New-Item -ItemType File -Path database\database.sqlite
#    En Linux/Mac:
# touch database/database.sqlite

# 6. Crear las tablas y cargar datos de prueba
php artisan migrate --seed
```

### Encender el servidor web

```bash
php artisan serve
```

Abre **http://127.0.0.1:8000** en el navegador.

### ⚙️ Encender el procesamiento de la cola (IMPORTANTE)

Las compras de YAuctions se procesan **en segundo plano**. Para que funcionen,
abre **otra terminal** y ejecuta:

```bash
cd compralatino-app
php artisan queue:work --queue=compras-yauctions
```

> Sin este comando las órdenes de YAuctions se quedan en estado *Pendiente*.

---

## 🔑 Cuentas de prueba

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador (BI) | `admin@compralatino.test` | `secret1234` |
| Cliente | `cliente1@compralatino.test` | `password` |

- **Tienda (cliente):** http://127.0.0.1:8000/
- **Panel de BI (admin):** http://127.0.0.1:8000/admin/dashboard

---

## 🗂️ Estructura del repositorio

- `compralatino-app/` → **proyecto Laravel ejecutable** (aquí se corre todo).
- `app/`, `routes/`, `database/`, `resources/` (en la raíz) → copia de referencia del código fuente de la Fase 3.

---

## 🧪 Notas de demostración

- La API de YAuctions está **simulada** dentro de la propia app
  (`YAuctionsFakeController`), así el proyecto corre sin internet.
- Tarjeta de prueba que **aprueba**: `4242 4242 4242 4242` (cualquier vencimiento futuro y CVV).
- Tarjeta que **rechaza** por fondos: `4000 0000 0000 0002`.
