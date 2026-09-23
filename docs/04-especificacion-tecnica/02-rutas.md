# Rutas

## Convenciones

- **Direcciones y nombres en español**, en minúscula y con guiones: la ruta `/seguimiento/sin-reclamar` se llama `seguimiento.sin-reclamar`.
- **Métodos HTTP:** GET muestra, POST crea o ejecuta una acción, PUT corrige y DELETE elimina. Los formularios envían PUT y DELETE con la directiva `@method` de Blade.
- **Acciones con verbos en español** (`mostrar`, `guardar`, `corregir`), no los nombres en inglés de los controladores de recursos de Laravel.
- **Después de guardar se redirige** a una pantalla. Así, recargar la página no repite la acción.
- **Los parámetros numéricos** se restringen con `whereNumber`, para que `/clientes/nuevo` no se confunda con un cliente.

## Protección

| Grupo | Middleware | Rutas |
| --- | --- | --- |
| **Sin sesión** | `guest` | Mostrar y enviar el inicio de sesión |
| **Límite de intentos** | `throttle:inicio-de-sesion` | Enviar el inicio de sesión (RNF-20) |
| **Con sesión** | `auth`, `auth.session` y `cache.headers:no_store;private` | Todas las demás, salvo las públicas |
| **Públicas** | Ninguno | La política de datos y la revisión de salud del monitor |
| **Archivos que sirve Nginx** | — | `manifest.webmanifest`, `sw.js`, `sin-conexion.html`, `css/`, `js/`, `fuentes/` y `.well-known/assetlinks.json` |

Todo formulario lleva `@csrf`; Laravel rechaza el envío sin ese token (RNF-23).

## Parámetros

Si el registro no existe o es de otro negocio, la respuesta es 404, como si no existiera (RNF-22). La prueba de aislamiento recorre todas las rutas con parámetros.

| Parámetro | Modelo | Se busca por | Cómo se aísla |
| --- | --- | --- | --- |
| `{cliente}` | `Cliente` | `id` | Filtro global por negocio (RN-01) |
| `{orden}` | `Orden` | `numero` | Filtro global por negocio; el número es único dentro del negocio (RN-08) |
| `{prenda}` | `Prenda` | `id` | Debe pertenecer a `{orden}`: enlace anidado de Laravel con `scopeBindings` |
| `{pago}` | `Pago` | `id` | Debe pertenecer a `{orden}`, igual que la prenda |
| `{foto}` | `Foto` | `id` | Enlace explícito (`Route::bind`) que la busca con `FotosDeOrden::foto()`, a través de su prenda y su orden |
| `{aviso}` | `Aviso` | `id` | Enlace explícito que lo busca a través de su orden |
| `{tipo}` | `TipoPrenda` | `id` | Filtro global por negocio |

## Tabla de rutas

**Pantallas sin ruta:** PT-19, porque es el mensaje que el cliente ve en WhatsApp.

### Acceso y públicas

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/entrar` | `sesion.formulario` | `SesionController@formulario` | PT-01 | HU-01 |
| POST | `/entrar` | `sesion.entrar` | `SesionController@entrar` | PT-01 | HU-01 |
| GET | `/entrar/google` | `sesion.google` | `SesionController@irAGoogle` | PT-01 | HU-37 |
| GET | `/entrar/google/respuesta` | `sesion.google.respuesta` | `SesionController@volverDeGoogle` | PT-01 | HU-37 |
| POST | `/salir` | `sesion.salir` | `SesionController@salir` | PT-23 | HU-01 |
| GET | `/politica-de-datos` | `politica-de-datos` | vista | — | — |
| GET | `/up` | — | Laravel | — | — |

`/politica-de-datos` muestra la política de tratamiento de datos (RNF-26). `/up` es la revisión de salud que trae Laravel y la consulta el monitor externo (RNF-16).

### Panel y seguimiento

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/` | `panel` | `PanelController@mostrar` | PT-02 | HU-32 |
| GET | `/seguimiento/atrasadas` | `seguimiento.atrasadas` | `SeguimientoController@atrasadas` | PT-20 | HU-33 |
| GET | `/seguimiento/sin-reclamar` | `seguimiento.sin-reclamar` | `SeguimientoController@sinReclamar` | PT-21 | HU-34 |

### Clientes

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/clientes` | `clientes.buscar` | `ClienteController@buscar` | PT-03 | HU-04 |
| GET | `/clientes/nuevo` | `clientes.nuevo` | `ClienteController@nuevo` | PT-04 | HU-03, HU-10 |
| POST | `/clientes` | `clientes.guardar` | `ClienteController@guardar` | PT-04 | HU-03, HU-10 |
| POST | `/clientes/desde-orden` | `clientes.desde-orden` | `ClienteController@desdeOrden` | PT-06 | HU-10 |
| GET | `/clientes/{cliente}` | `clientes.ficha` | `ClienteController@ficha` | PT-05 | HU-05 |
| GET | `/clientes/{cliente}/editar` | `clientes.editar` | `ClienteController@editar` | PT-04 | HU-06 |
| PUT | `/clientes/{cliente}` | `clientes.corregir` | `ClienteController@corregir` | PT-04 | HU-06 |

La búsqueda usa `?q=`, con parte del nombre o el celular.

### Órdenes

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/ordenes` | `ordenes.listar` | `OrdenController@listar` | PT-08 | HU-15 |
| GET | `/ordenes/nueva` | `ordenes.nueva` | `OrdenController@nueva` | PT-06 | HU-07, HU-09, HU-10, HU-24 |
| POST | `/ordenes` | `ordenes.guardar` | `OrdenController@guardar` | PT-06 | HU-07, HU-09, HU-17, HU-24 |
| GET | `/ordenes/{orden}/guardada` | `ordenes.guardada` | `OrdenController@guardada` | PT-07 | HU-08 |
| GET | `/ordenes/{orden}` | `ordenes.detalle` | `OrdenController@detalle` | PT-09 | HU-08, HU-14, HU-31 |
| GET | `/ordenes/{orden}/entregar` | `ordenes.confirmar-entrega` | `OrdenController@confirmarEntrega` | PT-16 | HU-21 |
| POST | `/ordenes/{orden}/entregar` | `ordenes.entregar` | `OrdenController@entregar` | PT-16 | HU-21 |
| GET | `/ordenes/{orden}/cancelar` | `ordenes.confirmar-cancelacion` | `OrdenController@confirmarCancelacion` | PT-17 | HU-22 |
| POST | `/ordenes/{orden}/cancelar` | `ordenes.cancelar` | `OrdenController@cancelar` | PT-17 | HU-22 |

La lista acepta dos parámetros:

- `?estado=` filtra por `en-proceso`, `lista`, `entregada` o `cancelada`.
- `?numero=` recibe `42` o `#0042`. Si la orden existe, redirige a su detalle (CA-15.2); si no, muestra la lista con el aviso de que no existe (CA-15.3).

### Prendas

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/ordenes/{orden}/prendas/nueva` | `prendas.nueva` | `PrendaController@nueva` | PT-06 | HU-11 |
| POST | `/ordenes/{orden}/prendas` | `prendas.agregar` | `PrendaController@agregar` | PT-09 | HU-11 |
| GET | `/ordenes/{orden}/prendas/{prenda}` | `prendas.acciones` | `PrendaController@acciones` | PT-11 | HU-20, HU-36 |
| POST | `/ordenes/{orden}/prendas/{prenda}/estado` | `prendas.cambiar-estado` | `PrendaController@cambiarEstado` | PT-11 | HU-20 |
| GET | `/ordenes/{orden}/prendas/{prenda}/editar` | `prendas.editar` | `PrendaController@editar` | PT-13 | HU-12, HU-13, HU-19 |
| PUT | `/ordenes/{orden}/prendas/{prenda}` | `prendas.corregir` | `PrendaController@corregir` | PT-13 | HU-12 |
| DELETE | `/ordenes/{orden}/prendas/{prenda}` | `prendas.eliminar` | `PrendaController@eliminar` | PT-13 | HU-13 |
| GET | `/ordenes/{orden}/prendas/{prenda}/devolver` | `prendas.confirmar-devolucion` | `PrendaController@confirmarDevolucion` | PT-12 | HU-36 |
| POST | `/ordenes/{orden}/prendas/{prenda}/devolver` | `prendas.devolver` | `PrendaController@devolver` | PT-12 | HU-36 |

Agregar una prenda a una orden existente reutiliza el formulario de prenda de PT-06, como en el mockup de PT-09.

### Fotos

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/ordenes/{orden}/fotos` | `fotos.de-orden` | `FotoController@deOrden` | PT-10 | HU-18 |
| POST | `/ordenes/{orden}/prendas/{prenda}/fotos` | `fotos.agregar` | `FotoController@guardar` | PT-10, PT-13 | HU-17 |
| GET | `/fotos/{foto}` | `fotos.mostrar` | `FotoController@mostrar` | PT-10 | HU-18 |
| DELETE | `/fotos/{foto}` | `fotos.eliminar` | `FotoController@eliminar` | PT-13 | HU-19 |

### Pagos y dinero

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/ordenes/{orden}/pagos/nuevo` | `pagos.nuevo` | `PagoController@nuevo` | PT-14 | HU-23 |
| POST | `/ordenes/{orden}/pagos` | `pagos.guardar` | `PagoController@guardar` | PT-14 | HU-23 |
| GET | `/ordenes/{orden}/pagos/{pago}/anular` | `pagos.confirmar-anulacion` | `PagoController@confirmarAnulacion` | PT-15 | HU-25 |
| POST | `/ordenes/{orden}/pagos/{pago}/anular` | `pagos.anular` | `PagoController@anular` | PT-15 | HU-25 |
| GET | `/dinero` | `dinero` | `DineroController@mostrar` | PT-22 | HU-26, HU-27 |

`/dinero` acepta `?periodo=hoy`, `semana`, `mes` o `fechas`; con `fechas`, también `desde` y `hasta`.

### Avisos

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/avisos` | `avisos.pendientes` | `AvisoController@pendientes` | PT-18 | HU-29, HU-30 |
| GET | `/avisos/{aviso}/whatsapp` | `avisos.abrir-whatsapp` | `AvisoController@abrirWhatsapp` | PT-18 | HU-29 |
| POST | `/avisos/{aviso}/enviado` | `avisos.confirmar-envio` | `AvisoController@confirmarEnvio` | PT-18 | HU-29 |

`avisos.abrir-whatsapp` arma el mensaje en ese momento (RN-42) y redirige al enlace de WhatsApp. No cambia el aviso: solo la confirmación lo marca como enviado (CA-29.4).

### Ajustes

| Método | Ruta | Nombre | Acción | Pantalla | Historias |
| --- | --- | --- | --- | --- | --- |
| GET | `/ajustes` | `ajustes` | `AjustesController@mostrar` | PT-23 | HU-02, HU-16, HU-35 |
| PUT | `/ajustes/contrasena` | `ajustes.contrasena` | `AjustesController@cambiarContrasena` | PT-23 | HU-02 |
| PUT | `/ajustes/plazo` | `ajustes.plazo` | `AjustesController@cambiarPlazo` | PT-23 | HU-35 |
| PUT | `/ajustes/taller` | `ajustes.taller` | `AjustesController@personalizar` | PT-23 | HU-38 |
| POST | `/ajustes/tipos-de-prenda` | `ajustes.tipos.agregar` | `AjustesController@agregarTipo` | PT-23 | HU-16 |
| PUT | `/ajustes/tipos-de-prenda/{tipo}` | `ajustes.tipos.renombrar` | `AjustesController@renombrarTipo` | PT-23 | HU-16 |
| PUT | `/ajustes/tipos-de-prenda/{tipo}/activo` | `ajustes.tipos.activo` | `AjustesController@cambiarActivoTipo` | PT-23 | HU-16 |

## Registrar un cliente sin salir de la orden

HU-10 pide no perder lo escrito en la orden:

1. En PT-06, «Registrar cliente nuevo» envía el formulario de la orden a `clientes.desde-orden`. Esa acción guarda lo escrito en la sesión, sin validarlo, y redirige a PT-04.
2. PT-04 muestra «Lo que escribiste en la orden se conserva» y guarda con `clientes.guardar`, con el campo oculto `desde=orden`.
3. Si el cliente es válido, redirige a `ordenes.nueva` con el cliente elegido y lo escrito (CA-10.1).
4. Si no es válido, PT-04 muestra el error y lo escrito sigue en la sesión (CA-10.2).
5. Lo escrito se borra de la sesión al guardar la orden.

Las fotos elegidas antes de registrar el cliente no se conservan, porque un archivo no se puede guardar en la sesión. PT-06 lo indica junto a las fotos.

## Acciones que no se pueden deshacer

RNF-10 pide confirmar antes de estas acciones. La confirmación se exige también en el servidor, no solo en la pantalla.

| Acción | Confirmación en pantalla | Ruta | Campo que exige el servidor |
| --- | --- | --- | --- |
| Cancelar una orden | PT-17 | `ordenes.cancelar` | `confirmacion=si` |
| Anular un pago | PT-15 | `pagos.anular` | `confirmacion=si` |
| Devolver una prenda sin arreglar | PT-12 | `prendas.devolver` | `confirmacion=si` |
| Eliminar una prenda | Cuadro de diálogo en PT-13 | `prendas.eliminar` | `confirmacion=si` |
| Eliminar una foto | Cuadro de diálogo en PT-13 | `fotos.eliminar` | `confirmacion=si` |
| Entregar una orden con saldo | Aviso de saldo en PT-16 (RN-21) | `ordenes.entregar` | `confirmacion=si`, cuando hay saldo |

Si falta el campo, nada cambia y se redirige a la pantalla de confirmación. Por eso CA-13.2, CA-21.5 y CA-36.2 se prueban en el servidor.

## Doble envío

RNF-14 pide que dos toques seguidos no dupliquen una orden ni un pago:

1. PT-06 y PT-14 llevan el campo oculto `token_formulario`, con un UUID nuevo cada vez que se abre el formulario.
2. El caso de uso busca primero un registro con ese token. Si ya existe, no crea otro y responde lo mismo que al primer envío: PT-07 de esa orden, o PT-09 con el pago.
3. Si los dos envíos llegan al mismo tiempo, la clave única de la base es la segunda barrera. El error de clave repetida se trata igual que el paso 2.
4. `app.js` deshabilita el botón al primer toque.

El abono inicial de una orden se guarda sin token propio: el token de la orden ya impide repetirlo.
