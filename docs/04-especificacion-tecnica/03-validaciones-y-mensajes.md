# Validaciones y mensajes

## Cómo se escriben los mensajes

RNF-09 pide que los errores digan qué pasó y cómo corregirlo.

- **En español y de tú**, como en los mockups: «Escribe el nombre del cliente».
- **Dicen qué hacer**, sin términos técnicos.
- **Van junto al campo** que los causó (`mensaje-error` en los mockups). Si no tienen campo, van en una banda arriba de la pantalla (`banda-error`).
- **`{saldo}` marca un dato que se reemplaza**, con el formato de RNF-08.
- **Los mensajes de los formularios** están en `messages()` de cada solicitud o en la llamada a `validate()`.
- **Los mensajes de las reglas del dominio** los lleva `ReglaIncumplida`, con el nombre del campo, y el controlador los devuelve como error de ese campo.
- **`lang/es/`** contiene solo lo que Laravel genera por su cuenta, con `APP_LOCALE=es`.

## Antes de validar

Cada solicitud normaliza algunos datos en `prepareForValidation`:

| Campo | Qué se hace | Ejemplo |
| --- | --- | --- |
| Celular | Se quitan los espacios (RN-03) | `310 456 7890` → `3104567890` |
| Precio, valor y abono | Se quitan el signo de pesos y los puntos de miles | `$25.000` → `25000` |
| Textos | Laravel quita los espacios de los extremos y convierte un texto vacío en nulo; lo hace por defecto | `"  Marta "` → `"Marta"` |

Un valor con centavos, como `15.000,50`, queda `15000,50` y no pasa la regla `integer` (RN-11).

## Solicitudes

### ClienteRequest

**Rutas:** `clientes.guardar`, `clientes.corregir`

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `nombre` | `required`, `string`, `max:120` | `required`, `string`: Escribe el nombre del cliente.<br>`max`: El nombre puede tener hasta 120 caracteres. | `clientes.nombre` | RN-02 |
| `celular` | `required`, y una regla que usa `Celular::desde` | `required`: Escribe el celular del cliente.<br>`Celular`: Escribe un celular colombiano de 10 dígitos que empiece por 3 | `clientes.celular` | RN-02, RN-03 |

### OrdenRequest

**Rutas:** `ordenes.guardar`

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `token_formulario` | `required`, `uuid` | `required`, `uuid`: La página se desactualizó. Vuelve a abrir el formulario. | `ordenes.token_formulario` | RNF-14 |
| `cliente_id` | `required`, `integer`, existe entre los clientes del negocio | `required`: Elige el cliente de la orden.<br>`exists`: Elige un cliente de la lista. | `ordenes.cliente_id` | RN-01, RN-05 |
| `fecha_entrega_acordada` | `required`, `date_format:Y-m-d`, `after_or_equal` a hoy según `Reloj` | `required`, `date_format`: Elige la fecha de entrega acordada.<br>`after_or_equal`: La entrega no puede ser antes de la fecha de recepción. | `ordenes.fecha_entrega_acordada` | RN-07, RN-09 |
| `prendas` | `required`, `array`, `min:1` | `required`, `min`: Agrega al menos una prenda. | — | RN-06 |
| `prendas.*.tipo_prenda_id` | `required`; un tipo activo del negocio, o `otro` | `required`: Elige el tipo de prenda.<br>`exists`: Elige un tipo de prenda de la lista. | `prendas.tipo_prenda_id` | RN-01, RN-10 |
| `prendas.*.tipo_otro` | `required_if` el tipo es `otro`, `string`, `max:60` | `required_if`: Escribe qué tipo de prenda es.<br>`max`: El tipo de prenda puede tener hasta 60 caracteres. | `tipos_prenda.nombre` | RN-10, RN-43 |
| `prendas.*.descripcion_arreglo` | `required`, `string`, `max:255` | `required`: Escribe qué arreglo lleva la prenda.<br>`max`: La descripción puede tener hasta 255 caracteres. | `prendas.descripcion_arreglo` | RN-10 |
| `prendas.*.precio` | `required`, `integer`, `min:1`, `max:4294967295` | `required`: Escribe el precio del arreglo.<br>`integer`: Escribe el precio en pesos, sin centavos.<br>`min`: El precio debe ser mayor que cero. | `prendas.precio` | RN-11 |
| `prendas.*.fotos` | `array`, `max:3` | `max`: Cada prenda puede tener hasta 3 fotos. | `fotos.posicion` | RN-17 |
| `prendas.*.fotos.*` | `image`, `mimes:jpg,jpeg,png,webp`, `max:10240` | `image`, `mimes`: La foto debe ser JPG, PNG o WebP.<br>`max`: La foto no puede pesar más de 10 MB.<br>`uploaded`: La foto no se pudo subir. Vuelve a intentarlo. | — | RNF-03 |
| `abono` | `nullable`, `integer`, `min:1` | `integer`: Escribe el abono en pesos, sin centavos.<br>`min`: El abono debe ser mayor que cero. | `pagos.valor` | RN-25, RN-28 |
| `metodo_pago_id` | `required_with:abono`, un método activo del negocio | `required_with`: Elige cómo pagó el abono. | `pagos.metodo_pago_id` | RN-01, RN-25 |

### PrendaRequest

**Rutas:** `prendas.agregar`, `prendas.corregir`

El tipo solo se valida al agregar: al corregir, una prenda no cambia de tipo (RF-12).

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `tipo_prenda_id` | `required` al agregar; un tipo activo del negocio, o `otro` | `required`: Elige el tipo de prenda. | `prendas.tipo_prenda_id` | RN-10 |
| `tipo_otro` | `required_if` el tipo es `otro`, `string`, `max:60` | `required_if`: Escribe qué tipo de prenda es. | `tipos_prenda.nombre` | RN-43 |
| `descripcion_arreglo` | `required`, `string`, `max:255` | `required`: Escribe qué arreglo lleva la prenda. | `prendas.descripcion_arreglo` | RN-10 |
| `precio` | `required`, `integer`, `min:1`, `max:4294967295` | `integer`: Escribe el precio en pesos, sin centavos.<br>`min`: El precio debe ser mayor que cero. | `prendas.precio` | RN-11 |

### FotoRequest

**Rutas:** `fotos.agregar`

Al registrar una orden, las fotos de cada prenda usan estos mismos mensajes.

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `fotos` | `required`, `array`, `max:3` | `required`: Toma o elige una foto.<br>`max`: Cada prenda puede tener hasta 3 fotos. | `fotos.posicion` | RN-17 |
| `fotos.*` | `image`, `mimes:jpg,jpeg,png,webp`, `max:10240` | `image`, `mimes`: La foto debe ser JPG, PNG o WebP.<br>`max`: La foto no puede pesar más de 10 MB.<br>`uploaded`: La foto no se pudo subir. Vuelve a intentarlo. | — | RNF-03 |

`AgregarFoto` cuenta también las fotos que la prenda ya tiene: si no caben todas las nuevas, responde «Cada prenda puede tener hasta 3 fotos.», y si ya tiene 3, el mensaje de RN-17. En ningún caso guarda una parte.

### PagoRequest

**Rutas:** `pagos.guardar`

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `token_formulario` | `required`, `uuid` | `required`, `uuid`: La página se desactualizó. Vuelve a abrir el formulario. | `pagos.token_formulario` | RNF-14 |
| `valor` | `required`, `integer`, `min:1`, `max:4294967295` | `required`: Escribe el valor del pago.<br>`integer`: Escribe el valor en pesos, sin centavos.<br>`min`: El valor debe ser mayor que cero. | `pagos.valor` | RN-25 |
| `metodo_pago_id` | `required`, un método activo del negocio | `required`: Elige cómo pagó. | `pagos.metodo_pago_id` | RN-01, RN-25 |

### AnulacionRequest

**Rutas:** `pagos.anular`

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `motivo_anulacion` | `required`, `string`, `max:255` | `required`: Escribe el motivo de la anulación.<br>`max`: El motivo puede tener hasta 255 caracteres. | `pagos.motivo_anulacion` | RN-31 |

### ContrasenaRequest

**Rutas:** `ajustes.contrasena`

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `contrasena_actual` | `required`, `current_password` | `required`: Escribe tu contraseña actual.<br>`current_password`: La contraseña actual no es correcta. | — | RNF-19 |
| `contrasena_nueva` | `required`, `string`, `min:8`, `max:72`, `confirmed` | `required`: Escribe la nueva contraseña.<br>`min`: La nueva contraseña debe tener al menos 8 caracteres.<br>`max`: La contraseña puede tener hasta 72 caracteres.<br>`confirmed`: Las dos contraseñas no coinciden. | — | RNF-19 |

El máximo de 72 caracteres existe porque bcrypt ignora lo que pase de ese largo. La columna `usuarios.contrasena` guarda el hash, no la contraseña, por eso no se compara con su largo.

## Validación en los controladores

Estos formularios son tan pequeños que se validan con `validate()` en el controlador, que sigue siendo parte de la capa Http (RNF-27).

### SesionController@entrar

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `usuario` | `required`, `string`, `max:60` | `required`: Escribe tu usuario.<br>`max`, `auth.failed`: Usuario o contraseña incorrectos.<br>`auth.throttle`: Hiciste demasiados intentos. Espera {segundos} segundos y vuelve a intentarlo. | `usuarios.usuario` | RN-01 |
| `contrasena` | `required`, `string` | `required`: Escribe tu contraseña. | — | RNF-20 |

El error de inicio de sesión es uno solo y no dice si falló el usuario o la contraseña (CA-01.2).

### AjustesController@cambiarPlazo

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `dias_sin_reclamar` | `required`, `integer`, `between:1,365` | `required`, `integer`, `between`: El plazo debe estar entre 1 y 365 días. | `negocios.dias_sin_reclamar` | RN-35 |

### AjustesController@renombrarTipo

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `nombre` | `required`, `string`, `max:60`, único entre los tipos del negocio | `required`: Escribe el nombre del tipo de prenda.<br>`max`: El tipo de prenda puede tener hasta 60 caracteres.<br>`unique`: Ya existe un tipo de prenda con ese nombre. | `tipos_prenda.nombre` | RN-43 |

### PrendaController@cambiarEstado

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `estado` | `required`, `in:pendiente,en_proceso,terminada` | `required`, `in`: Elige uno de los estados que se muestran. | `prendas.estado` | RN-13, RN-14 |

La validación solo acepta los valores posibles. Si el cambio está permitido desde el estado actual, lo decide `TransicionesDePrenda`.

### OrdenController@listar

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `estado` | `nullable`, `in:en-proceso,lista,entregada,cancelada` | — | — | RN-18 |
| `numero` | `nullable`, `regex:/^#?\d+$/` | — | `ordenes.numero` | RN-08 |

Un filtro inválido no muestra error: se ignora y se ven todas las órdenes.

### DineroController@mostrar

| Campo | Reglas | Mensajes | Columna | Regla de negocio |
| --- | --- | --- | --- | --- |
| `periodo` | `nullable`, `in:hoy,semana,mes,fechas` | — | — | RN-33 |
| `desde`, `hasta` | `required_if:periodo,fechas`, `date_format:Y-m-d`; `hasta` con `after_or_equal:desde` | `required_if`: Elige las dos fechas.<br>`after_or_equal`: La fecha final no puede ser antes de la inicial. | — | RN-09, RN-33 |

## Mensajes de las reglas del dominio

Los lanza `ReglaIncumplida` desde el dominio o el caso de uso.

| Regla | Cuándo | Campo | Mensaje |
| --- | --- | --- | --- |
| **RN-06** | Se intenta eliminar la única prenda de la orden | — | No puedes eliminar la única prenda de la orden. Si el cliente ya no quiere el arreglo, cancela la orden. |
| **RN-13** | Se intenta pasar a Entregada una prenda que no está Terminada | `estado` | Una prenda solo se entrega cuando está Terminada. |
| **RN-15** | Se intenta modificar una prenda Entregada o Devuelta | — | Esta prenda ya fue {estado} y no se puede modificar. |
| **RN-16** | Corregir, eliminar o devolver una prenda dejaría lo pagado por encima del valor | `precio` | La orden quedaría valiendo {valor} y ya tiene {pagado} pagados. Primero anula el pago que sobra. |
| **RN-17** | Se intenta agregar una cuarta foto a una prenda | `fotos` | Esta prenda ya tiene 3 fotos. Elimina una para agregar otra. |
| **RN-18** | Se intenta agregar una prenda a una orden Entregada | — | Solo se pueden agregar prendas a una orden en proceso o lista para entregar. |
| **RN-21** | Se va a entregar una orden con saldo | — | {cliente} debe {saldo}. ¿Entregar de todos modos? |
| **RN-24** | Se intenta cambiar algo en una orden Cancelada | — | Esta orden está cancelada y no admite cambios. |
| **RN-24** | Se intenta cancelar una orden Entregada | — | Una orden entregada no se puede cancelar. |
| **RN-28** | El pago supera el saldo | `valor` | El pago no puede superar el saldo pendiente de {saldo} |
| **RN-28** | El abono inicial supera el valor de la orden | `abono` | El abono no puede superar el valor de la orden: {valor}. |
| **RN-30** | Se intenta registrar un pago en una orden Cancelada | — | No se pueden registrar pagos en una orden cancelada. |
| **RN-44** | Se intenta devolver una prenda que no está Pendiente ni En proceso | — | Solo se puede devolver sin arreglar una prenda Pendiente o En proceso. |
| **RN-44** | Es la única prenda por resolver de la orden | — | Es la única prenda por resolver de la orden. Si el cliente se la lleva sin arreglar, cancela la orden. |

El mensaje de RN-28 no lleva punto final, igual que el criterio CA-23.3 y el mockup de PT-14.

## Mensajes informativos

No son errores: orientan a la usuaria.

| Pantalla | Cuándo | Mensaje | Historia |
| --- | --- | --- | --- |
| **PT-03** | La búsqueda no encuentra clientes | No hay clientes con «{busqueda}». | HU-04 |
| **PT-03** | El negocio todavía no tiene clientes | Todavía no hay clientes registrados. | HU-04 |
| **PT-05** | El cliente no tiene órdenes | {cliente} no tiene órdenes y no debe nada. | HU-05 |
| **PT-05** | El cliente tiene órdenes y todas están pagadas o canceladas | No debe nada | HU-05 |
| **PT-05** | Una orden del cliente está cancelada | No suma a la deuda | HU-05 |
| **PT-05** | Se corrigieron los datos del cliente | Los datos de {cliente} quedaron actualizados. | HU-06 |
| **PT-06** | Una prenda se guarda sin foto | Sin foto: tómale una para reconocerla después. | HU-17 |
| **PT-06** | Se registra un cliente nuevo desde la orden, o la orden vuelve con un error | Vuelve a elegir las fotos que ya habías tomado. | HU-10, HU-17 |
| **PT-06**, **PT-13** | Se eligen fotos, con JavaScript | {cantidad} fotos elegidas. | HU-17 |
| **PT-13** | Se guardaron las fotos | La foto quedó guardada. / Las fotos quedaron guardadas. | HU-17 |
| **PT-13** | La prenda ya tiene 3 fotos | Ya tiene las 3 fotos que caben. | HU-17 |
| **PT-07** | La orden se guardó | Escribe este número en la bolsa. | HU-08 |
| **PT-07** | La orden se guardó | La orden de {cliente} quedó guardada. | HU-07 |
| **PT-08** | El número buscado no existe | No hay una orden con el número {numero}. | HU-15 |
| **PT-08** | Lo buscado no es un número de orden | Escribe solo el número de la bolsa, por ejemplo 42. | HU-15 |
| **PT-08** | No hay órdenes en el estado elegido | No hay órdenes {estado}. | HU-15 |
| **PT-09** | Se corrigió una prenda | Los cambios de la prenda quedaron guardados. | HU-12 |
| **PT-10** | Siempre, sobre las fotos | Toca una foto para verla grande y comparar con las prendas del rincón. | HU-18 |
| **PT-10** | Una prenda no tiene fotos | Esta prenda no tiene fotos. | HU-18 |
| **PT-13** | La orden ya tiene pagos | La orden no puede valer menos de lo ya pagado ({pagado}). | HU-12 |
| **PT-13** | Se escribe un precio distinto | El valor de la orden pasará de {valor} a {nuevo_valor} y el saldo a {saldo}. | HU-12 |
| **PT-13** | Se elimina una prenda o una foto | ¿Eliminar {que}? No se puede deshacer. | HU-13, HU-19 |
| **PT-18** | No hay avisos por enviar | No hay avisos por enviar. | HU-29 |
| **PT-23** | La contraseña cambió | Tu contraseña cambió. Se cerró la sesión en los demás dispositivos. | HU-02 |

## Páginas de error

Nunca muestran detalles técnicos: en producción `APP_DEBUG=false`.

| Código | Cuándo | Mensaje |
| --- | --- | --- |
| **404** | La dirección no existe o es de otro negocio (RNF-22) | No encontramos lo que buscas. |
| **419** | El formulario estuvo abierto más que la sesión | La página estuvo abierta mucho tiempo. Vuelve a intentarlo. |
| **429** | Demasiadas solicitudes seguidas | Hiciste demasiados intentos. Espera un momento y vuelve a intentarlo. |
| **500** | Un error inesperado | Algo salió mal. Vuelve a intentarlo en un momento. |
| **503** | Durante un despliegue | Estamos actualizando el sistema. Vuelve a intentarlo en un minuto. |

Todas llevan un botón para volver al panel del día.
