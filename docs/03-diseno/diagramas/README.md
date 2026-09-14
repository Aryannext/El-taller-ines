# Diagramas de diseño

**Estado:** borrador · DOC-18 · adelantado del Sprint 2 · se valida con el instructor

## Para qué sirven

Muestran el diseño desde los ángulos que pide el desarrollo:

| Diagrama | Qué responde |
| --- | --- |
| **Clases** | Qué clases hay y cómo se relacionan |
| **Estados** | Por qué estados pasan las prendas, las órdenes, los avisos y los pagos |
| **Secuencia** | Cómo colaboran las clases en cada acción importante |
| **Componentes** | De qué partes está hecho el sistema |
| **Despliegue** | Dónde se ejecuta cada parte |

Salen de la [arquitectura](../arquitectura/README.md) y del [modelo de datos](../modelo-de-datos/README.md), y no los contradicen: `python scripts/verificar_diagramas.py` comprueba que las clases existan en la estructura de carpetas, que los atributos de los modelos sean columnas reales del esquema y que los estados sean exactamente los que permiten la base de datos y las reglas.

**Notación:** UML dibujado con Mermaid, que GitHub muestra directamente. Mermaid no tiene la notación UML de componentes ni de despliegue, así que esos dos se dibujan como diagramas de bloques con estereotipos (`«componente»`, `«dispositivo»`, `«servidor»`).

## Índice

| # | Diagrama | Tipo |
| --- | --- | --- |
| 1 | [Clases del dominio](#1-clases-del-dominio) | Clases |
| 2 | [Clases de los modelos](#2-clases-de-los-modelos) | Clases |
| 3 | [Casos de uso e implementaciones](#3-casos-de-uso-e-implementaciones) | Clases |
| 4 | [Estados de una prenda](#4-estados-de-una-prenda) | Estados |
| 5 | [Estados de una orden](#5-estados-de-una-orden) | Estados |
| 6 | [Estados de un aviso](#6-estados-de-un-aviso) | Estados |
| 7 | [Estados de un pago](#7-estados-de-un-pago) | Estados |
| 8 | [Registrar una orden](#8-registrar-una-orden) | Secuencia |
| 9 | [Enviar un aviso asistido](#9-enviar-un-aviso-asistido) | Secuencia |
| 10 | [Devolver una prenda sin arreglar](#10-devolver-una-prenda-sin-arreglar) | Secuencia |
| 11 | [Ver una foto privada](#11-ver-una-foto-privada) | Secuencia |
| 12 | [Componentes](#12-componentes) | Componentes |
| 13 | [Despliegue](#13-despliegue) | Despliegue |

Otras dos secuencias ya están en la arquitectura: [la última prenda queda terminada y sale el aviso](../arquitectura/README.md#la-última-prenda-queda-terminada-y-sale-el-aviso) y [registrar un pago que supera el saldo](../arquitectura/README.md#registrar-un-pago-que-supera-el-saldo).

---

## 1. Clases del dominio

Las reglas del negocio en PHP puro. No dependen de Laravel ni de la base de datos: reciben datos simples y devuelven decisiones.

```mermaid
classDiagram
    direction LR
    class EstadoDePrenda {
        <<enumeration>>
        Pendiente
        EnProceso
        Terminada
        Entregada
        Devuelta
    }
    class TransicionesDePrenda {
        +permitidas(EstadoDePrenda desde) array
        +exigir(EstadoDePrenda desde, EstadoDePrenda hacia) void
        +puedeModificarse(EstadoDePrenda estado) bool
        +puedeDevolverse(EstadoDePrenda estado) bool
    }
    class EstadoDeOrden {
        <<enumeration>>
        EnProceso
        ListaParaEntregar
        Entregada
        Cancelada
        +calcular(array estadosDePrendas, bool cancelada)$ EstadoDeOrden
    }
    class NumeroDeOrden {
        -int valor
        +desde(int valor)$ NumeroDeOrden
        +siguiente() NumeroDeOrden
        +formato() string
    }
    class ReglasDeSeguimiento {
        +estaAtrasada(EstadoDeOrden estado, DateTimeImmutable entrega, DateTimeImmutable hoy) bool
        +diasDeAtraso(DateTimeImmutable entrega, DateTimeImmutable hoy) int
        +diasDeEspera(DateTimeImmutable listaEn, DateTimeImmutable hoy) int
        +estaSinReclamar(EstadoDeOrden estado, DateTimeImmutable listaEn, DateTimeImmutable hoy, int plazo) bool
    }
    class OrdenQuedoLista {
        +int ordenId
        +DateTimeImmutable listaEn
    }
    class Dinero {
        -int pesos
        +pesos(int valor)$ Dinero
        +sumar(Dinero otro) Dinero
        +restar(Dinero otro) Dinero
        +esMayorQue(Dinero otro) bool
        +formato() string
    }
    class CalculadoraDeSaldo {
        +valor(array preciosYEstados) Dinero
        +saldo(Dinero valor, array pagosNoAnulados) Dinero
        +estadoDePago(Dinero saldo) string
    }
    class ReglasDeValor {
        +exigirValorNoMenorQuePagado(Dinero nuevoValor, Dinero pagado) void
    }
    class Celular {
        -string numero
        +desde(string texto)$ Celular
        +enFormatoInternacional() string
    }
    class MensajeDeAviso {
        +construir(string cliente, NumeroDeOrden numero, int prendasListas, Dinero saldo)$ MensajeDeAviso
        +texto() string
        +parametros() array
    }
    class CanalDeAviso {
        <<interface>>
        +estaDisponible() bool
        +enviar(Celular destino, MensajeDeAviso mensaje) ResultadoDeEnvio
    }
    class ResultadoDeEnvio {
        +bool aceptado
        +string canal
        +string idMensaje
        +string error
    }
    class AlmacenDeFotos {
        <<interface>>
        +guardar(string rutaTemporal, string carpeta) array
        +entregar(string ruta) mixed
        +eliminar(string ruta) void
    }
    class Reloj {
        <<interface>>
        +ahora() DateTimeImmutable
        +hoy() DateTimeImmutable
    }
    class ReglaIncumplida {
        <<exception>>
        +string regla
        +string mensajeParaUsuaria
    }
    TransicionesDePrenda ..> EstadoDePrenda : decide sobre
    TransicionesDePrenda ..> ReglaIncumplida : lanza
    EstadoDeOrden ..> EstadoDePrenda : se calcula desde
    ReglasDeSeguimiento ..> EstadoDeOrden : consulta
    CalculadoraDeSaldo ..> Dinero : devuelve
    ReglasDeValor ..> Dinero : compara
    ReglasDeValor ..> ReglaIncumplida : lanza
    MensajeDeAviso ..> NumeroDeOrden : usa
    MensajeDeAviso ..> Dinero : usa
    CanalDeAviso ..> Celular : envía a
    CanalDeAviso ..> MensajeDeAviso : envía
    CanalDeAviso ..> ResultadoDeEnvio : devuelve
```

- **Objetos de valor:** `Dinero`, `Celular` y `NumeroDeOrden` no se pueden crear con un valor inválido; así una regla como RN-03 se comprueba en un solo lugar.
- **`ReglaIncumplida`** lleva el código de la regla y el mensaje en español que la vista muestra junto al campo (RNF-09).
- **Interfaces:** `CanalDeAviso`, `AlmacenDeFotos` y `Reloj` son las únicas; las implementa la infraestructura (diagrama 3).

## 2. Clases de los modelos

Un modelo Eloquent por tabla, con las mismas relaciones y cardinalidades del modelo de datos. Los atributos son columnas reales del [esquema](../modelo-de-datos/esquema.sql).

```mermaid
classDiagram
    %% verificar: modelos
    class PerteneceANegocio {
        <<trait>>
        +bootPerteneceANegocio()$ void
        +negocio() BelongsTo
    }
    class Negocio {
        +string nombre
        +int dias_sin_reclamar
    }
    class Usuario {
        +string nombre
        +string usuario
        +string contrasena
    }
    class Cliente {
        +string nombre
        +string celular
        +ordenes() HasMany
    }
    class TipoPrenda {
        +string nombre
        +bool activo
    }
    class MetodoPago {
        +string nombre
        +bool activo
    }
    class Orden {
        +int numero
        +date fecha_entrega_acordada
        +datetime recibida_en
        +datetime lista_en
        +datetime cancelada_en
        +cliente() BelongsTo
        +prendas() HasMany
        +pagos() HasMany
        +avisos() HasMany
    }
    class Prenda {
        +string descripcion_arreglo
        +int precio
        +EstadoDePrenda estado
        +datetime entregada_en
        +datetime devuelta_en
        +fotos() HasMany
    }
    class Foto {
        +int posicion
        +string ruta
        +int bytes
    }
    class Pago {
        +int valor
        +datetime pagado_en
        +datetime anulado_en
        +string motivo_anulacion
    }
    class Aviso {
        +datetime ciclo_lista_en
        +string estado
        +string canal
        +string mensaje
        +int intentos
        +datetime resuelto_en
    }
    Usuario ..|> PerteneceANegocio : usa
    Cliente ..|> PerteneceANegocio : usa
    TipoPrenda ..|> PerteneceANegocio : usa
    MetodoPago ..|> PerteneceANegocio : usa
    Orden ..|> PerteneceANegocio : usa
    Negocio "1" --> "0..*" Usuario : tiene
    Negocio "1" --> "0..*" Cliente : atiende
    Negocio "1" --> "0..*" TipoPrenda : define
    Negocio "1" --> "0..*" MetodoPago : acepta
    Cliente "1" --> "0..*" Orden : deja
    Orden "1" *-- "1..*" Prenda : agrupa
    TipoPrenda "1" --> "0..*" Prenda : clasifica
    Prenda "1" *-- "0..3" Foto : tiene
    Orden "1" --> "0..*" Pago : recibe
    MetodoPago "1" --> "0..*" Pago : clasifica
    Orden "1" --> "0..*" Aviso : genera
```

- **Composición** (rombo lleno): una prenda no existe sin su orden, y una foto no existe sin su prenda. La orden tiene al menos una prenda (RN-06) y la prenda hasta tres fotos (RN-17).
- **`PerteneceANegocio`** se usa en las tablas que tienen `negocio_id`: las raíces de ADR-002 y `Orden`, por ADR-004. Prendas, fotos, pagos y avisos se filtran a través de su orden.
- **`Prenda.estado`** se convierte automáticamente a la enumeración `EstadoDePrenda` del dominio.

## 3. Casos de uso e implementaciones

Cómo los controladores llaman a los casos de uso, y cómo estos dependen de interfaces del dominio y no de sus implementaciones (inversión de dependencias). Se muestran los casos de uso que tocan las tres interfaces; los demás siguen el mismo patrón.

```mermaid
classDiagram
    direction LR
    class PrendaController {
        +cambiarEstado(Prenda prenda) Response
        +devolver(Prenda prenda) Response
    }
    class PagoController {
        +guardar(PagoRequest solicitud, Orden orden) Response
    }
    class FotoController {
        +guardar(Prenda prenda) Response
        +mostrar(int foto) Response
    }
    class CambiarEstadoDePrenda {
        +ejecutar(Prenda prenda, EstadoDePrenda nuevo) void
    }
    class DevolverPrendaSinArreglar {
        +ejecutar(Prenda prenda) void
    }
    class SincronizarEstadoDeOrden {
        +sincronizar(Orden orden) EstadoDeOrden
    }
    class RegistrarPago {
        +ejecutar(Orden orden, int valor, MetodoPago metodo, string token) Pago
    }
    class AgregarFoto {
        +ejecutar(Prenda prenda, string rutaTemporal) Foto
    }
    class GenerarAviso {
        +handle(OrdenQuedoLista evento) void
    }
    class EnviarAviso {
        <<ShouldQueue>>
        +int tries
        +backoff() array
        +handle(CanalDeAviso canal, Reloj reloj) void
    }
    class CanalDeAviso {
        <<interface>>
    }
    class AlmacenDeFotos {
        <<interface>>
    }
    class Reloj {
        <<interface>>
    }
    class WhatsAppCloudApiCanal {
        +estaDisponible() bool
        +enviar(Celular destino, MensajeDeAviso mensaje) ResultadoDeEnvio
    }
    class WhatsAppAsistidoCanal {
        +estaDisponible() bool
        +enviar(Celular destino, MensajeDeAviso mensaje) ResultadoDeEnvio
        +enlace(Celular destino, MensajeDeAviso mensaje) string
    }
    class AlmacenLocalPrivado {
        +guardar(string rutaTemporal, string carpeta) array
        +entregar(string ruta) mixed
        +eliminar(string ruta) void
    }
    class RelojDeColombia {
        +ahora() DateTimeImmutable
        +hoy() DateTimeImmutable
    }
    class AppServiceProvider {
        +register() void
    }
    PrendaController --> CambiarEstadoDePrenda
    PrendaController --> DevolverPrendaSinArreglar
    PagoController --> RegistrarPago
    FotoController --> AgregarFoto
    CambiarEstadoDePrenda --> SincronizarEstadoDeOrden
    DevolverPrendaSinArreglar --> SincronizarEstadoDeOrden
    SincronizarEstadoDeOrden --> Reloj
    SincronizarEstadoDeOrden ..> GenerarAviso : evento OrdenQuedoLista
    GenerarAviso ..> EnviarAviso : encola
    EnviarAviso --> CanalDeAviso
    AgregarFoto --> AlmacenDeFotos
    WhatsAppCloudApiCanal ..|> CanalDeAviso
    WhatsAppAsistidoCanal ..|> CanalDeAviso
    AlmacenLocalPrivado ..|> AlmacenDeFotos
    RelojDeColombia ..|> Reloj
    AppServiceProvider ..> CanalDeAviso : enlaza implementaciones
```

- **`EnviarAviso`** pregunta a `WhatsAppCloudApiCanal` si está disponible; si no lo está, o si falla después de tres intentos (`tries`), el aviso queda para `WhatsAppAsistidoCanal` (RN-40, ADR-003).
- **`WhatsAppAsistidoCanal.enlace()`** arma la dirección `https://wa.me/57…?text=…` que abre WhatsApp con el mensaje escrito.
- **En las pruebas**, `AppServiceProvider` se reemplaza por un canal falso, un almacén en memoria y un reloj fijo en el miércoles 16 de septiembre de 2026.

## 4. Estados de una prenda

```mermaid
stateDiagram-v2
    %% verificar: prendas.estado
    [*] --> Pendiente: se registra (RN-12)
    Pendiente --> EnProceso: empieza el arreglo
    Pendiente --> Terminada: lo termina de una vez
    EnProceso --> Terminada: termina el arreglo
    Terminada --> EnProceso: retoque al medírsela (RN-14)
    Terminada --> Entregada: se entrega la orden (RN-13, RN-20)
    Pendiente --> Devuelta: se la lleva sin arreglar (RN-44)
    EnProceso --> Devuelta: se la lleva sin arreglar (RN-44)
    Entregada --> [*]
    Devuelta --> [*]
    EnProceso: En proceso
    note right of Entregada
        No se edita ni cambia de estado (RN-15)
    end note
```

| Desde | Hacia | Cuándo | Acción | Regla |
| --- | --- | --- | --- | --- |
| — | Pendiente | Al registrar la prenda | `RegistrarOrden`, `AgregarPrenda` | RN-12 |
| Pendiente | En proceso | La dueña empieza el arreglo | `CambiarEstadoDePrenda` | RN-12 |
| Pendiente o En proceso | Terminada | Termina el arreglo | `CambiarEstadoDePrenda` | RN-12 |
| Terminada | En proceso | Al medírsela, le falta algo | `CambiarEstadoDePrenda` | RN-14 |
| Terminada | Entregada | Se entrega la orden; no desde la prenda sola | `EntregarOrden` | RN-13 · RN-20 |
| Pendiente o En proceso | Devuelta | El cliente se la lleva sin arreglar | `DevolverPrendaSinArreglar` | RN-44 |

Cualquier otro cambio lo rechaza `TransicionesDePrenda`. Si la orden está cancelada, ninguno está permitido (RN-24).

## 5. Estados de una orden

El estado de la orden no se guarda ni se cambia a mano (RN-18, RN-19): `EstadoDeOrden` lo calcula cada vez desde sus prendas.

```mermaid
stateDiagram-v2
    %% verificar: RN-18
    [*] --> EnProceso: se registra con sus prendas
    EnProceso --> EnProceso: entrega parcial (RN-20)
    EnProceso --> ListaParaEntregar: ninguna prenda Pendiente ni En proceso y al menos una Terminada
    ListaParaEntregar --> EnProceso: retoque o prenda agregada (RN-14, HU-11)
    ListaParaEntregar --> Entregada: se entregan las prendas Terminadas (RN-20)
    EnProceso --> Entregada: se devuelve la última prenda pendiente y las demás ya se entregaron (RN-44)
    EnProceso --> Cancelada: la dueña la cancela (RN-24)
    ListaParaEntregar --> Cancelada: la dueña la cancela (RN-24)
    Entregada --> [*]
    Cancelada --> [*]
    EnProceso: En proceso
    ListaParaEntregar: Lista para entregar
    note left of EnProceso
        Atrasada si la fecha acordada ya pasó (RN-34)
    end note
    note right of ListaParaEntregar
        Al entrar se registra lista_en y se genera el aviso (RN-22, RN-37).
        Sin reclamar después del plazo del negocio (RN-35)
    end note
```

**Atrasada** y **sin reclamar** no son estados: son condiciones que se calculan sobre En proceso y Lista para entregar.

## 6. Estados de un aviso

```mermaid
stateDiagram-v2
    %% verificar: avisos.estado
    [*] --> EnCola: la orden queda lista (RN-37)
    EnCola --> EnCola: la API falla y se reintenta (RNF-17)
    EnCola --> Enviado: la API oficial acepta el mensaje
    EnCola --> PendienteAsistido: API sin configurar o tres intentos fallidos (RN-40)
    EnCola --> Descartado: la orden ya no está lista (RN-39)
    PendienteAsistido --> Enviado: la dueña confirma el envío asistido (HU-29)
    PendienteAsistido --> Descartado: la orden deja de estar lista (HU-30)
    Enviado --> [*]
    Descartado --> [*]
    EnCola: En cola
    PendienteAsistido: Pendiente de envío asistido
```

Cada vez que la orden vuelve a quedar lista nace un aviso nuevo; la base impide dos avisos para la misma vez (RN-38).

## 7. Estados de un pago

```mermaid
stateDiagram-v2
    %% verificar: RN-31
    [*] --> Valido: se registra sin superar el saldo (RN-28)
    Valido --> Anulado: se anula con motivo (RN-31)
    Valido --> [*]
    Anulado --> [*]
    Valido: Válido
    note right of Anulado
        No se borra y no cuenta en el saldo (RN-27, RN-31)
    end note
```

## 8. Registrar una orden

Muestra la transacción completa, la numeración sin repetidos (RN-08) y el tipo de prenda escrito con «Otro» (RN-43). Corresponde a PT-06 y PT-07.

```mermaid
sequenceDiagram
    actor duena as Dueña
    participant ctrl as OrdenController
    participant solicitud as OrdenRequest
    participant caso as RegistrarOrden
    participant tipos as ResolverTipoDePrenda
    participant calc as CalculadoraDeSaldo
    participant numero as NumeroDeOrden
    participant bd as MySQL
    duena->>ctrl: Guardar la orden de Rosa: vestido, overol y abono de $10.000
    ctrl->>solicitud: validar cliente, fecha, prendas y abono
    solicitud-->>ctrl: datos completos (RN-07, RN-10)
    ctrl->>caso: ejecutar(datos, token)
    caso->>bd: abrir transacción y bloquear la fila del negocio
    loop cada prenda
        caso->>tipos: resolver(«Overol»)
        tipos->>bd: buscar sin tildes ni mayúsculas y crear si no existe (RN-43)
    end
    caso->>calc: valor(precios)
    calc-->>caso: $43.000 (RN-26)
    caso->>caso: ¿el abono supera el valor? (RN-28)
    caso->>bd: mayor número de orden del negocio
    caso->>numero: siguiente()
    numero-->>caso: #0047 (RN-08)
    caso->>bd: guardar orden, prendas y abono con el token (RN-06, RNF-14)
    caso->>bd: confirmar transacción (RNF-13)
    ctrl-->>duena: PT-07 con la etiqueta #0047
```

## 9. Enviar un aviso asistido

Cuando la API oficial no está configurada, la dueña envía el aviso desde su WhatsApp con el mensaje ya escrito. Corresponde a PT-18.

```mermaid
sequenceDiagram
    actor duena as Dueña
    participant ctrl as AvisoController
    participant consulta as AvisosPorEnviar
    participant mensaje as MensajeDeAviso
    participant canal as WhatsAppAsistidoCanal
    participant whatsapp as WhatsApp del celular
    participant caso as ConfirmarEnvioAsistido
    participant bd as MySQL
    duena->>ctrl: Abrir avisos por enviar
    ctrl->>consulta: listar()
    consulta->>bd: avisos pendientes de órdenes que siguen listas (RN-39)
    consulta->>mensaje: construir con el saldo actual (RN-42)
    ctrl-->>duena: aviso a Ana con el mensaje redactado
    duena->>ctrl: Abrir WhatsApp y enviar
    ctrl->>canal: enlace(celular, mensaje)
    canal-->>ctrl: https://wa.me/573205550101?text=…
    ctrl-->>whatsapp: abre el chat con el mensaje escrito
    duena->>whatsapp: toca enviar
    duena->>ctrl: Sí, ya lo envié
    ctrl->>caso: ejecutar(aviso)
    caso->>bd: aviso enviado por envío asistido, con su mensaje (RN-41)
```

## 10. Devolver una prenda sin arreglar

Muestra una consecuencia que aparece solo al diagramar: al devolver la última prenda pendiente de la #0042, las demás están terminadas y la orden queda lista, lo que genera un aviso. Corresponde a PT-12.

```mermaid
sequenceDiagram
    actor duena as Dueña
    participant ctrl as PrendaController
    participant caso as DevolverPrendaSinArreglar
    participant trans as TransicionesDePrenda
    participant calc as CalculadoraDeSaldo
    participant valor as ReglasDeValor
    participant sinc as SincronizarEstadoDeOrden
    participant bd as MySQL
    duena->>ctrl: Sí, devolver la camisa sin arreglar
    ctrl->>caso: ejecutar(camisa)
    caso->>bd: abrir transacción y bloquear la orden
    caso->>trans: ¿se puede devolver una prenda Pendiente? (RN-44)
    trans-->>caso: sí
    caso->>caso: ¿quedan otras prendas sin devolver? (RN-06)
    caso->>calc: valor sin la camisa
    calc-->>caso: $23.000 (RN-26)
    caso->>valor: exigirValorNoMenorQuePagado($23.000, $10.000)
    valor-->>caso: se cumple (RN-16)
    caso->>bd: camisa Devuelta con su fecha (RN-44)
    caso->>sinc: sincronizar(orden)
    sinc-->>caso: Lista para entregar: el pantalón y la otra camisa están terminados (RN-18)
    sinc->>bd: registrar lista_en (RN-22)
    caso->>bd: confirmar transacción
    Note over sinc: después de confirmar se emite OrdenQuedoLista y se genera el aviso (RN-37)
    ctrl-->>duena: saldo de $13.000 y botón Entregar
```

## 11. Ver una foto privada

Las fotos no están en una carpeta pública (RNF-25): cada una se entrega solo con sesión y si es del propio negocio. Corresponde a PT-10.

```mermaid
sequenceDiagram
    actor duena as Dueña
    participant ctrl as FotoController
    participant consulta as FotosDeOrden
    participant almacen as AlmacenDeFotos
    duena->>ctrl: pedir la foto 3
    ctrl->>ctrl: ¿hay sesión iniciada? (RNF-25)
    ctrl->>consulta: foto(3), a través de la orden del negocio de la sesión (RN-01)
    alt la foto no existe o es de otro negocio
        consulta-->>ctrl: no encontrada
        ctrl-->>duena: 404, como si no existiera (RNF-22)
    else es del negocio
        consulta-->>ctrl: ruta en el disco privado
        ctrl->>almacen: entregar(ruta)
        almacen-->>ctrl: archivo
        ctrl-->>duena: imagen
    end
```

## 12. Componentes

```mermaid
flowchart LR
    subgraph http["«componente» Http"]
        rutas["Rutas web"] --> controladores["Controladores"]
        controladores --> solicitudes["Solicitudes: validación"]
        controladores --> vistas["Vistas Blade"]
    end
    subgraph aplicacion["«componente» Aplicación"]
        casos["Casos de uso<br/>Clientes · Órdenes · Fotos · Pagos · Avisos · Configuración"]
        consultas["Consultas de las pantallas"]
    end
    subgraph dominio["«componente» Dominio"]
        reglas["Reglas<br/>Clientes · Órdenes · Pagos · Avisos"]
        interfaces["Interfaces<br/>CanalDeAviso · AlmacenDeFotos · Reloj"]
    end
    subgraph modelos["«componente» Modelos"]
        eloquent["Modelos Eloquent<br/>y filtro por negocio"]
    end
    subgraph infraestructura["«componente» Infraestructura"]
        canales["Canales de WhatsApp"]
        almacen["Almacén local privado"]
        reloj["Reloj de Colombia"]
    end
    subgraph laravel["«marco» Laravel"]
        autenticacion["Autenticación"]
        cola["Cola y eventos"]
        archivos["Almacenamiento de archivos"]
        basededatos["Conexión a la base de datos"]
    end
    controladores --> casos
    controladores --> consultas
    controladores --> autenticacion
    casos --> reglas
    consultas --> reglas
    casos --> interfaces
    casos --> eloquent
    consultas --> eloquent
    casos --> cola
    canales -. "implementa" .-> interfaces
    almacen -. "implementa" .-> interfaces
    reloj -. "implementa" .-> interfaces
    eloquent --> basededatos
    almacen --> archivos
    basededatos --> mysql[("MySQL 8.4")]
    canales --> api["WhatsApp Cloud API"]
```

| Componente | Ofrece | Necesita |
| --- | --- | --- |
| **Http** | Las pantallas PT-01 a PT-23 | Aplicación y la autenticación de Laravel |
| **Aplicación** | Un caso de uso por acción y las consultas de lectura | Dominio, Modelos, la cola y las interfaces |
| **Dominio** | Reglas, objetos de valor e interfaces | Nada |
| **Modelos** | Acceso a las 10 tablas con el filtro por negocio | La conexión a la base de datos |
| **Infraestructura** | Implementaciones de `CanalDeAviso`, `AlmacenDeFotos` y `Reloj` | Dominio, la API de WhatsApp y el almacenamiento de Laravel |

## 13. Despliegue

La propuesta para el VPS. Las versiones y rutas se confirman al hacer HT-04.

```mermaid
flowchart TB
    subgraph android["«dispositivo» Celular Android"]
        apk["APK con Trusted Web Activity"] --> chrome["Chrome"]
    end
    subgraph otro["«dispositivo» Otro celular o computador"]
        navegador["Navegador"]
    end
    subgraph vps["«servidor» VPS · Linux"]
        nginx["Nginx<br/>HTTPS 443 · HTTP 80 redirige<br/>certificado Let's Encrypt"]
        phpfpm["PHP-FPM 8.4<br/>aplicación Laravel · sistema/"]
        trabajador["Trabajador de la cola<br/>servicio que se reinicia solo"]
        cron["Programador<br/>schedule:run cada minuto"]
        mysql[("MySQL 8.4<br/>solo en localhost")]
        fotos[("storage/app/privado<br/>fotos")]
        respaldos[("Respaldos diarios<br/>14 días")]
    end
    dns["DNS del dominio"]
    whatsapp["WhatsApp Cloud API"]
    drive["Google Drive"]
    monitor["Monitor externo<br/>cada 5 minutos"]
    github["GitHub<br/>código y GitHub Actions"]
    chrome -- "HTTPS" --> nginx
    navegador -- "HTTPS" --> nginx
    dns -. "resuelve el dominio" .-> nginx
    nginx --> phpfpm
    phpfpm --> mysql
    phpfpm --> fotos
    trabajador --> mysql
    trabajador -- "HTTPS" --> whatsapp
    cron --> respaldos
    respaldos -- "copia semanal" --> drive
    monitor -- "HTTPS" --> nginx
    github -. "git pull al desplegar" .-> phpfpm
```

| Nodo | Qué corre | Requisito |
| --- | --- | --- |
| **Celular Android** | El APK abre el sistema en Chrome a pantalla completa | RNF-35 · ADR-006 |
| **Nginx** | Recibe HTTPS con certificado de Let's Encrypt y redirige lo que llegue por HTTP; publica `assetlinks.json` | RNF-18 · ADR-006 |
| **PHP-FPM 8.4** | La aplicación Laravel, configurada solo con variables de entorno | RNF-34 |
| **Trabajador de la cola** | `php artisan queue:work` como servicio del sistema, para que se reinicie solo | RNF-17 · HT-04 |
| **Programador** | Respaldo diario de la base y las fotos; copia semanal a Google Drive | RNF-15 · HT-05 |
| **MySQL 8.4** | Solo acepta conexiones del mismo servidor | RNF-23 |
| **Monitor externo** | Revisa el sistema cada 5 minutos | RNF-16 |
| **GitHub** | Guarda el código y corre las pruebas; el despliegue trae el código aprobado | RNF-30 |

## Decisiones que salieron al diagramar

| Hallazgo | Diagrama | Propuesta |
| --- | --- | --- |
| **Aviso con el cliente presente.** Si al devolver una prenda sin arreglar la orden queda lista, se genera un aviso aunque el cliente esté en el taller y se lleve todo enseguida | 10 | Esperar unos minutos antes de enviar el aviso automático. Si en ese tiempo la orden se entrega, el aviso se descarta solo (RN-39). No contradice ninguna regla; queda **por decidir antes de construir HU-28** (Sprint 4) y, si se acepta, se agrega a sus criterios |
| **La foto se busca por su orden.** `Foto` no tiene `negocio_id`, así que no puede usar el filtro global | 11 | `FotosDeOrden` busca la foto a través de su prenda y su orden, que sí filtran por negocio. Queda cubierto por la prueba de aislamiento (RNF-22) |
| **Pendiente no vuelve de En proceso.** Ninguna regla lo pide, y no cambia el estado de la orden | 4 | No se permite; si la dueña marca En proceso por error, no afecta ningún cálculo |

## Pendiente

- Decidir, antes de construir HU-28, si el aviso automático espera unos minutos antes de enviarse.
- Los diagramas y la especificación de los casos de uso están en [casos de uso](../casos-de-uso/README.md) (DOC-15).
