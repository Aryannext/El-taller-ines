# Datos y modelos

## Migraciones

- **Una migración por tabla**, en el orden de sus llaves foráneas: negocios, usuarios, clientes, tipos de prenda, métodos de pago, órdenes, prendas, fotos, pagos y avisos.
- **Deben producir exactamente [`esquema.sql`](../03-diseno/modelo-de-datos/esquema.sql):** tipos, nulos, valores por defecto, índices, llaves, comentarios de tablas y columnas, y restricciones `CHECK`. Lo que el constructor de esquemas de Laravel no expresa, como los `CHECK` y la llave foránea compuesta de ADR-004, se escribe con `DB::statement`. GitHub Actions compara el resultado con `esquema.sql` (RNF-31).
- **Juego de caracteres:** `utf8mb4` con intercalación `utf8mb4_0900_ai_ci`, fijados en `config/database.php`.
- **Fechas:** `dateTime('creado_en')->useCurrent()` y, donde corresponde, `useCurrentOnUpdate()`.
- **Nombre del archivo:** `AAAA_MM_DD_HHMMSS_crear_tabla_ordenes.php`.
- **Solo hacia adelante en producción:** una migración desplegada no se edita. Un cambio es una migración nueva.

### Tablas de Laravel fuera del modelo de datos

| Tabla | Para qué | Se conserva |
| --- | --- | --- |
| `migrations` | Registro de las migraciones aplicadas | Sí |
| `jobs`, `job_batches` y `failed_jobs` | La cola de los avisos (ADR-003) | Sí; las crea la migración de trabajos del esqueleto |
| `users`, `password_reset_tokens` y `sessions` | Usuarios y sesiones de ejemplo del esqueleto | No: se borra su migración, porque la usuaria es `usuarios` y la sesión va en archivos |
| `cache` y `cache_locks` | Caché en base de datos | No: la caché va en archivos |

La comparación de RNF-31 ignora las tablas de Laravel que se conservan.

## Seeders

| Seeder | Qué crea | Cuándo se usa |
| --- | --- | --- |
| `NegocioInicialSeeder` | El negocio, la usuaria, los 6 tipos de prenda iniciales (RF-16) y los métodos de pago Efectivo y Nequi (RN-25) | Al instalar (RNF-33). Toma el usuario y la contraseña de `USUARIA_INICIAL_USUARIO` y `USUARIA_INICIAL_CONTRASENA`, y falla si están vacías |
| `DatosDeLosMockupsSeeder` | Los datos de ejemplo del modelo de datos, con las fechas corridas para que «hoy» sea el día en que se carga | Pruebas manuales y demostración |
| `VolumenDeTresAniosSeeder` | 500 clientes, 750 órdenes, 2.200 prendas y 1.500 pagos | PM-06, con HT-06 |

Las pruebas automáticas no usan seeders: cada una arma sus datos con las fábricas de `database/factories/`.

Después de instalar, la usuaria cambia su contraseña (HU-02) y se borra `USUARIA_INICIAL_CONTRASENA` del servidor.

## Modelos Eloquent

| Modelo | Tabla | CREATED_AT | UPDATED_AT | Filtro por negocio | Clave en la ruta | Conversiones |
| --- | --- | --- | --- | --- | --- | --- |
| `Negocio` | `negocios` | `creado_en` | `actualizado_en` | — | — | `dias_sin_reclamar`: entero |
| `Usuario` | `usuarios` | `creado_en` | `actualizado_en` | No: el inicio de sesión busca en todos los negocios | — | `contrasena`: hash |
| `Cliente` | `clientes` | `creado_en` | `actualizado_en` | Sí | `id` | — |
| `TipoPrenda` | `tipos_prenda` | `creado_en` | `actualizado_en` | Sí | `id` | `activo`: booleano |
| `MetodoPago` | `metodos_pago` | `creado_en` | `actualizado_en` | Sí | — | `activo`: booleano |
| `Orden` | `ordenes` | `recibida_en` | `actualizado_en` | Sí | `numero` | `fecha_entrega_acordada`: fecha inmutable; `recibida_en`, `lista_en` y `cancelada_en`: fecha y hora inmutables |
| `Prenda` | `prendas` | `creado_en` | `actualizado_en` | A través de su orden | `id` | `estado`: `EstadoDePrenda`; `entregada_en` y `devuelta_en`: fecha y hora inmutables |
| `Foto` | `fotos` | `creado_en` | — | A través de su prenda y su orden | `id` | — |
| `Pago` | `pagos` | `pagado_en` | `actualizado_en` | A través de su orden | `id` | `anulado_en`: fecha y hora inmutable |
| `Aviso` | `avisos` | `generado_en` | `actualizado_en` | A través de su orden | `id` | `ciclo_lista_en` y `resuelto_en`: fecha y hora inmutables |

### Detalles

- **Nombres de las marcas de tiempo:** cada modelo define `CREATED_AT` y `UPDATED_AT` con las columnas en español. En `Foto`, `UPDATED_AT` es `null` porque la foto no se modifica.
- **Usuaria:** `Usuario` usa `$authPasswordName = 'contrasena'` y devuelve `token_recordar` en `getRememberTokenName()`. `config/auth.php` apunta a `App\Modelos\Usuario`.
- **Estado de la prenda:** `EstadoDePrenda` es una enumeración del dominio con los valores del ENUM de la base: `pendiente`, `en_proceso`, `terminada`, `entregada` y `devuelta`. Que un modelo use una clase del dominio respeta la regla de dependencias, porque la flecha va hacia adentro (ADR-005).
- **Sin asignación masiva abierta:** cada modelo declara `$fillable` con las columnas que llenan sus casos de uso. `negocio_id` no está en esa lista; lo asigna `PerteneceANegocio`.

### Relaciones

| Modelo | Relaciones |
| --- | --- |
| `Negocio` | Tiene muchos usuarios, clientes, tipos de prenda, métodos de pago y órdenes |
| `Cliente` | Pertenece a un negocio; tiene muchas órdenes |
| `Orden` | Pertenece a un cliente; tiene muchas prendas, pagos y avisos; tiene muchas fotos a través de sus prendas |
| `Prenda` | Pertenece a una orden y a un tipo de prenda; tiene hasta 3 fotos |
| `Pago` | Pertenece a una orden y a un método de pago |
| `Aviso` | Pertenece a una orden |

### Filtro por negocio

`PerteneceANegocio` es el filtro global de las tablas raíz (ADR-002):

- **Con sesión:** agrega `negocio_id` de la usuaria a toda consulta y lo asigna al crear.
- **En una solicitud web sin sesión:** la consulta no devuelve nada. Así, un error de configuración de una ruta no expone datos de ningún negocio.
- **En la cola y en la consola** no hay sesión y el filtro no se aplica. Por eso `EnviarAviso` busca su aviso por el identificador que recibió, y ningún trabajo lista datos de varios negocios.

### Cálculos sobre muchas órdenes

El panel, la lista de quién debe y las listas de seguimiento cargan las órdenes con sus prendas y pagos por adelantado (`with`), para no hacer una consulta por prenda (RNF-02). El saldo y el estado se calculan con `CalculadoraDeSaldo` y `EstadoDeOrden`, la única fuente de esas reglas.

Si la medición de PM-06 muestra que no alcanza, se usa la consulta `resumen_ordenes` del modelo de datos, probada contra la calculadora con los mismos datos.

## Zona horaria y formatos

| Qué | Cómo | Requisito |
| --- | --- | --- |
| **Zona de la aplicación** | `'timezone' => 'America/Bogota'` en `config/app.php`. Va fija en el código, no en una variable de entorno, porque es una regla de negocio y no depende del entorno | RN-09 |
| **Zona de la conexión** | `'timezone' => '-05:00'` en `config/database.php`; Colombia no cambia de horario | RN-09 |
| **Hoy y ahora** | Siempre `Reloj`. Nunca `now()`, `date()` ni `time()` en el dominio ni en la aplicación | RN-09 |
| **Dinero** | `Dinero::formato()`: `$15.000`, con punto de miles y sin decimales | RNF-08 |
| **Fecha** | Directiva `@fecha`: `14 sep 2026`, con los meses `ene feb mar abr may jun jul ago sep oct nov dic` | RNF-08 |
| **Fecha con día** | Directiva `@fechaConDia`: `Sábado 19 sep 2026`, como en PT-06 | RNF-08 |
| **Hora** | Directiva `@hora`: `4:12 p. m.` | RNF-08 |
| **Número de orden** | `NumeroDeOrden::formato()`: `#0042`. Desde la orden 10.000 muestra todos los dígitos | RN-08 |
| **Celular** | Directiva `@celular`: `310 456 7890` | RN-03 |

Las directivas de Blade se registran en `AppServiceProvider`. Los nombres de los meses van escritos en el código, para no depender de las traducciones de otra librería.
