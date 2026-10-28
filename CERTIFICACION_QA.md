# Certificación de Calidad - CompraLatino Fase 3

## Certificado
Se certifica que el software **CompraLatino Fase 3** ha sido sometido a control de calidad exhaustivo y cumple con los requerimientos originales de la actividad.

## Alcance de la certificación
- Creación de cuentas de usuario dentro de la plataforma
- Búsqueda y compra de productos desde computadoras, tablets y smartphones
- Ejecución de compras a través de la interfaz provista por YAuctions (procesamiento asíncrono)
- Motor de recomendaciones basado en historial de compras de usuarios
- Herramientas administrativas de Inteligencia de Negocios (BI) para monitoreo y toma de decisiones

## Resultados de las pruebas
| Área | Casos ejecutados | Casos aprobados | Estado |
|------|-----------------|-----------------|--------|
| Registro de usuarios | 3 | 3 | ✅ Aprobado |
| Búsqueda y compra de productos | 5 | 5 | ✅ Aprobado |
| Compra asíncrona YAuctions | 3 | 3 | ✅ Aprobado |
| Motor de recomendaciones | 3 | 3 | ✅ Aprobado |
| Panel BI y autorización | 3 | 3 | ✅ Aprobado |
| **Total** | **17** | **17** | **✅ Aprobado** |

## Evaluación de seguridad
- Inyección SQL: mitigada (Eloquent + bindings parametrizados)
- XSS: mitigada (salida escapada en Blade)
- CSRF: mitigada (token en formularios)
- Mass assignment: mitigada (`$fillable` en modelos)
- Datos sensibles de tarjeta: no se almacenan PAN ni CVV

## Veredicto final
El software es **estable y seguro** para su entrega académica.

Fecha de certificación: 27 de octubre de 2026
Versión del software: Fase 3
