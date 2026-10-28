# Plan de Pruebas - CompraLatino Fase 3

## Objetivo
Verificar que el software cumple con los requerimientos originales de la Fase 3 y que es estable y seguro para su entrega.

## Alcance
- Registro de usuarios y creación de cuentas
- Búsqueda y compra de productos (catálogo, checkout con tarjeta)
- Ejecución de compras vía interfaz YAuctions (procesamiento asíncrono en cola)
- Motor de recomendaciones basado en historial de usuario
- Panel de Inteligencia de Negocios (BI) para administradores

## Casos de prueba

### 1. Registro de usuarios
| ID | Caso | Resultado esperado |
|----|------|-------------------|
| R1 | Registro con correo nuevo y contraseña válida | Cuenta creada, rol 'cliente', sesión iniciada |
| R2 | Registro con correo duplicado | Error de validación, no se crea cuenta |
| R3 | Registro con contraseña menor a 8 caracteres | Error de validación |

### 2. Búsqueda y compra de productos
| ID | Caso | Resultado esperado |
|----|------|-------------------|
| C1 | Buscar producto por texto en catálogo | Se muestran productos coincidentes |
| C2 | Filtrar por categoría y plataforma | Se muestran solo productos del filtro |
| C3 | Comprar producto local con tarjeta válida | Orden creada, stock descontado, pago procesado |
| C4 | Comprar producto YAuctions con tarjeta válida | Orden 'pendiente', job en cola, sincronización asíncrona |
| C5 | Comprar con tarjeta rechazada (4000...0002) | No se crea orden, error mostrado en checkout |

### 3. Interfaz YAuctions (compra asíncrona)
| ID | Caso | Resultado esperado |
|----|------|-------------------|
| Y1 | Orden YAuctions enviada a cola | Respuesta instantánea, orden 'pendiente' |
| Y2 | Worker procesa orden YAuctions | Orden pasa a 'completada' con acuse YAU-XXX |
| Y3 | Fallo de comunicación con YAuctions | Orden 'fallida', auditoría registrada en JSON |

### 4. Motor de recomendaciones
| ID | Caso | Resultado esperado |
|----|------|-------------------|
| M1 | Usuario con historial de compras | Se muestran recomendaciones por categoría |
| M2 | Usuario sin historial | Se muestran productos más populares |
| M3 | Vista de producto registra evento | Evento 'vista' guardado en user_history |

### 5. Panel de Inteligencia de Negocios (BI)
| ID | Caso | Resultado esperado |
|----|------|-------------------|
| B1 | Administrador accede a panel BI | KPIs mostrados (ingresos, órdenes, ticket promedio) |
| B2 | Cliente intenta acceder a panel BI | Redirigido (403/302), acceso denegado |
| B3 | Auditoría de fallos muestra errores de sincronización | Tabla con órdenes fallidas y detalle JSON |

## Entregables QA
- Este plan de pruebas
- Informe de incidencias y bugs
- Certificación de estabilidad y seguridad
