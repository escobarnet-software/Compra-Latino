# Informe de Incidencias y Bugs - CompraLatino Fase 3

## Resumen
Se realizó control de calidad exhaustivo sobre el software de la Fase 3. A continuación se detallan las incidencias detectadas, su estado y las acciones correctivas aplicadas.

## Incidencias detectadas

| ID | Módulo | Descripción | Severidad | Estado | Acción correctiva |
|----|--------|-------------|-----------|--------|-------------------|
| I-01 | Checkout | Botón 'Comprar' del catálogo enviaba POST directo a `/ordenes` sin campos de tarjeta, causando error de validación | Alta | Resuelto | Cambiado a enlace GET hacia `/checkout` con parámetros de producto |
| I-02 | Cola YAuctions | `catch (ConnectionException)` no capturaba `ConnectException` de Guzzle ante host inaccesible, perdiendo auditoría de fallos | Media | Resuelto | Agregado `catch (Throwable)` con detección de fallos de conexión por nombre de clase |
| I-03 | Panel BI | Consulta con JOIN entre `orders` y `products` generaba columna ambigua `created_at`, causando error 500 | Alta | Resuelto | Calificada columna como `orders.created_at` |
| I-04 | Vista cacheada | El servidor servía vista Blade obsoleta con formulario POST viejo después de cambios | Baja | Resuelto | Limpieza de caché de vistas (`view:clear`) |

## Vulnerabilidades de seguridad evaluadas

| ID | Área | Evaluación | Mitigación |
|----|------|------------|------------|
| S-01 | Inyección SQL | Bajo | Consultas mediante Eloquent con bindings parametrizados |
| S-02 | XSS | Bajo | Salida escapada con `{{ }}` en Blade |
| S-03 | CSRF | Bajo | Token `@csrf` en todos los formularios POST |
| S-04 | Mass assignment | Bajo | `$fillable` definido en todos los modelos |
| S-05 | Datos sensibles | Bajo | PAN y CVV nunca se persisten ni se registran en logs |

## Pruebas ejecutadas
- Registro de usuarios: 3 casos (aprobados)
- Búsqueda y compra: 5 casos (aprobados)
- Compra asíncrona YAuctions: 3 casos (aprobados)
- Motor de recomendaciones: 3 casos (aprobados)
- Panel BI y autorización: 3 casos (aprobados)

## Conclusión
Todas las incidencias críticas fueron resueltas. El software es estable para su entrega.
