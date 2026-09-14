# ADR-002 · Un solo taller, con los datos preparados para varios negocios

**Estado:** aceptada · **Fecha:** 13 de septiembre de 2026

## Contexto

- **Idea de negocio:** el sistema será gratuito para mujeres con negocios pequeños de arreglo de ropa ([idea de negocio](../../01-problema/idea-de-negocio.md)). A futuro, varios negocios usarán la misma instalación.
- **Plazo:** la entrega del 13 de octubre de 2026 atiende a un solo taller.
- **Riesgo de no prepararse:** agregar la separación por negocio después obliga a modificar casi todas las tablas, las consultas y las pruebas.
- **Riesgo de hacerlo completo ahora:** registro de negocios, invitación de usuarias y administración suman trabajo que no cabe en 30 días.
- **Evaluación:** el modelo de datos debe cumplir la tercera forma normal (3FN).

## Decisión

Separación por columna en una sola base de datos, preparada pero sin funciones de gestión de negocios.

1. **Tabla `negocio`.** En esta entrega tiene un solo registro: el taller.
2. **La columna `negocio_id` va solo en las tablas raíz**, las que no dependen de otra tabla que ya pertenezca a un negocio: `usuario`, `cliente` y la configuración del negocio. Los catálogos que cada negocio pueda personalizar (por ejemplo, tipos de prenda) se definen en el modelo de datos del Sprint 2.
3. **Las tablas dependientes heredan el negocio por su relación** y no repiten la columna: una orden pertenece a un cliente, una prenda y un pago pertenecen a una orden. Repetir `negocio_id` en ellas sería una dependencia transitiva y rompería la 3FN.
4. **Un solo punto de código aplica el filtro.** En Laravel, un *global scope* aplicado desde un trait filtra cada consulta por el negocio de la usuaria autenticada. Ningún controlador ni servicio filtra a mano.
5. **Fuera de esta entrega:** crear negocios, registro público de usuarias y panel de administración.

## Alternativas consideradas

| Alternativa | Por qué no |
| --- | --- |
| No preparar nada y agregarlo cuando haga falta | Reestructurar tablas, consultas y pruebas con datos reales en producción es el escenario más caro y riesgoso |
| Varios negocios completos desde esta entrega | No cabe en 30 días junto con la documentación exigida |
| Una base de datos por negocio | Multiplica migraciones, respaldos y monitoreo; excesivo para negocios de 40 a 60 prendas al mes |
| Paquete de *multi-tenancy* (por ejemplo, `stancl/tenancy`) | Dependencia y configuración mayores de lo que el caso necesita; un scope propio es más fácil de explicar y mantener |
| `negocio_id` en todas las tablas | Evita algunos *joins*, pero duplica un dato derivable y rompe la 3FN sin que el volumen lo justifique |

## Consecuencias

- **Crecer no obliga a reestructurar:** atender varios negocios será agregar el registro de negocios y sus pantallas, no cambiar el modelo de datos.
- **El modelo respeta la 3FN:** el negocio de una orden, prenda o pago se obtiene por su relación.
- **Costo en consultas:** filtrar órdenes, prendas o pagos por negocio requiere unir con `cliente`. Con el volumen esperado es despreciable, y las llaves foráneas llevan índice. Si algún día el volumen lo exige, se evaluará una desnormalización documentada en un ADR nuevo.
- **Riesgo de que un negocio vea datos de otro:** si una consulta evitara el scope. Se mitiga con dos medidas desde el Sprint 3: una regla de código (ninguna consulta desactiva el scope sin justificarlo) y una prueba automática que crea dos negocios y verifica que ninguno ve los datos del otro.
