# Avisos, fotos y reloj

Estas son las tres partes del sistema que hablan con algo externo, a través de las interfaces `CanalDeAviso`, `AlmacenDeFotos` y `Reloj` (ADR-005).

## Cómo sale un aviso

1. **La orden queda lista.** Todo caso de uso que cambia las prendas llama a `SincronizarEstadoDeOrden`: cambiar el estado, eliminar o devolver una prenda. Si la orden pasa a Lista para entregar, registra `lista_en` (RN-22) y programa el evento con `DB::afterCommit(fn () => event(new OrdenQuedoLista(...)))`. El evento sale solo si la transacción se confirma. `OrdenQuedoLista` es del dominio y no puede implementar interfaces de Laravel, por eso el «después de confirmar» lo decide la aplicación.
2. **La orden deja de estar lista.** Si una prenda vuelve a En proceso o se agrega una prenda, `SincronizarEstadoDeOrden` borra `lista_en` y marca `descartado` los avisos `en_cola` o `pendiente_asistido` de esa orden (RN-39, CA-30.2).
3. **Se crea el aviso.** `GenerarAviso` escucha el evento y crea el aviso `en_cola`, con `ciclo_lista_en` igual a `lista_en`. Si ya existe uno para esa vez, la clave única lo impide y no crea otro (RN-38). Después encola `EnviarAviso` con el identificador del aviso.
4. **Se envía en segundo plano.** `EnviarAviso` corre en el trabajador de la cola:
   - Si el aviso ya no está `en_cola`, termina.
   - Si la orden ya no está lista, lo marca `descartado` (RN-39, CA-30.1).
   - Si la API no está configurada, lo deja `pendiente_asistido`, sin mensaje. El mensaje se arma al abrir WhatsApp, con los datos de ese momento (RN-42).
   - Si no, suma un intento, arma `MensajeDeAviso` con el saldo actual y llama al canal.
5. **Resultado.** Si el canal acepta, el aviso queda `enviado`, con canal, mensaje, identificador de WhatsApp y `resuelto_en` (RN-41). Qué pasa si falla está en la tabla de respuestas.

### La cola

| Parámetro | Valor | Requisito |
| --- | --- | --- |
| Conexión | `database`, con `QUEUE_CONNECTION` | ADR-003 |
| Nombre de la cola | `avisos` | — |
| Intentos | 3, con `$tries` | RNF-17 |
| Esperas entre intentos | 30 segundos y 2 minutos, con `backoff()` | RNF-17 |
| Tiempo máximo por intento | 30 segundos, con `$timeout` | — |
| Al agotar los intentos | `failed()` deja el aviso `pendiente_asistido` | RN-40 |
| Trabajos fallidos | Quedan en `failed_jobs` y se limpian cada semana los de más de 14 días | — |

## WhatsApp Cloud API

### Plantilla

| Campo | Valor |
| --- | --- |
| **Nombre** | `orden_lista` |
| **Categoría** | Utilidad: informa sobre una orden del cliente, no es publicidad |
| **Idioma** | `es` |
| **Cuerpo** | `Hola {{1}}, tu orden {{2}} del taller está lista para recoger. Prendas listas: {{3}}. Saldo pendiente: {{4}}. Te esperamos.` |
| **Ejemplo que se envía a Meta** | Marta · #0042 · 3 · $21.000 |

| Variable | Valor | Sale de |
| --- | --- | --- |
| `{{1}}` | Primer nombre del cliente | La primera palabra del nombre registrado |
| `{{2}}` | Número de la orden | `NumeroDeOrden::formato()` |
| `{{3}}` | Cantidad de prendas Terminadas | Las prendas de la orden al momento del envío |
| `{{4}}` | Saldo pendiente | `Dinero::formato()`, al momento del envío (RN-42) |

`MensajeDeAviso::parametros()` devuelve los cuatro valores en ese orden, y `texto()` devuelve el mismo mensaje ya completo, que es el que se usa en el envío asistido.

**Diferencia con el mockup PT-19:** el mockup dice «3 prendas». La plantilla dice «Prendas listas: 3» para no escribir «1 prendas» cuando hay una sola. El mockup ya advertía que el texto final dependía de la plantilla que aprobara Meta.

### Solicitud

```http
POST https://graph.facebook.com/{WHATSAPP_VERSION_API}/{WHATSAPP_ID_NUMERO}/messages
Authorization: Bearer {WHATSAPP_TOKEN}
Content-Type: application/json
```

```json
{
  "messaging_product": "whatsapp",
  "to": "573104567890",
  "type": "template",
  "template": {
    "name": "orden_lista",
    "language": { "code": "es" },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "Marta" },
          { "type": "text", "text": "#0042" },
          { "type": "text", "text": "3" },
          { "type": "text", "text": "$21.000" }
        ]
      }
    ]
  }
}
```

- `to` es `Celular::enFormatoInternacional()`: el indicativo 57 y el celular, sin el signo `+`.
- La conexión espera hasta 5 segundos y la respuesta hasta 10.
- `WhatsAppCloudApiCanal::estaDisponible()` es verdadero solo si `WHATSAPP_TOKEN`, `WHATSAPP_ID_NUMERO` y `WHATSAPP_VERSION_API` tienen valor: la versión también hace falta para armar la dirección. `config/services.php` los lee en la clave `whatsapp`.

### Respuestas

| Respuesta | Qué hace el canal | Qué pasa con el aviso |
| --- | --- | --- |
| 200 con `messages[0].id` | Devuelve `ResultadoDeEnvio` aceptado, con el identificador | `enviado` (RN-41) |
| Sin conexión, tiempo agotado, error 5xx o 429 | Lanza una excepción: es un error temporal | La cola reintenta; al tercer intento fallido, `pendiente_asistido` (RNF-17) |
| Otro error 4xx: token vencido, número sin WhatsApp o plantilla no aprobada | Devuelve `ResultadoDeEnvio` no aceptado, con el error | `pendiente_asistido` de inmediato, porque reintentar no lo arregla (RN-40) |

- El canal falso de las pruebas responde de las mismas tres formas (plan de pruebas).
- El registro de Laravel guarda el identificador del aviso y el código de error de Meta. Nunca guarda el token ni el celular.

**Límite conocido.** Si Meta acepta el mensaje pero la respuesta no llega por un corte de conexión, la cola lo reintenta y el cliente podría recibirlo dos veces. La API no ofrece una forma de marcar un envío como repetido. Se acepta, porque es poco probable y un aviso repetido no causa daño; CA-28.4 prueba el caso normal, en el que la API sí responde.

**Fuera de esta entrega:** confirmar si el cliente recibió o leyó el mensaje (ADR-003).

## Evolution API

ADR-007 cambia el canal automático: si Evolution API está configurada, `EvolutionApiCanal` envía el aviso; si no, se usa `WhatsAppCloudApiCanal`. La cola, los reintentos, el envío asistido y la constancia no cambian.

### Solicitud

```http
POST {EVOLUTION_URL}/message/sendText/{EVOLUTION_INSTANCIA}
apikey: {EVOLUTION_API_KEY}
Content-Type: application/json
```

```json
{
  "number": "573104567890",
  "text": "Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $21.000. Te esperamos."
}
```

- `number` es `Celular::enFormatoInternacional()` y `text` es `MensajeDeAviso::texto()`: no hay plantilla.
- `EvolutionApiCanal::estaDisponible()` es verdadero solo si `EVOLUTION_URL`, `EVOLUTION_API_KEY` y `EVOLUTION_INSTANCIA` tienen valor.
- La conexión espera hasta 5 segundos y la respuesta hasta 20.

### Respuestas

| Respuesta | Qué hace el canal | Qué pasa con el aviso |
| --- | --- | --- |
| 2xx con `key.id` | Devuelve `ResultadoDeEnvio` aceptado, con el identificador | `enviado`, con canal `evolution_api` (RN-41) |
| Sin conexión, tiempo agotado, error 5xx o 429 | Lanza una excepción: es un error temporal, como una sesión caída | La cola reintenta; al tercer intento fallido, `pendiente_asistido` (RNF-17) |
| Otro error 4xx: número sin WhatsApp, instancia inexistente o clave errada | Devuelve `ResultadoDeEnvio` no aceptado con el código HTTP | `pendiente_asistido` de inmediato (RN-40) |

- El error registrado es solo el código HTTP: la respuesta de Evolution API repite el celular, y el registro no lo guarda.

## Envío asistido

- `WhatsAppAsistidoCanal::enlace()` devuelve `https://wa.me/573104567890?text=` seguido del texto del mensaje codificado con `rawurlencode`.
- `estaDisponible()` siempre es verdadero, porque solo necesita el WhatsApp del celular de la usuaria.
- `AvisosPorEnviar` lista los avisos `pendiente_asistido` cuyas órdenes siguen listas, con el mensaje armado en ese momento (diagrama 9).
- `avisos.abrir-whatsapp` redirige al enlace y no cambia el aviso.
- `ConfirmarEnvioAsistido` comprueba que la orden siga lista y marca el aviso `enviado`, con canal asistido, el mensaje de ese momento y `resuelto_en` (RN-41). Si la orden ya no está lista, lo descarta y avisa que la orden cambió.

## Fotos

### Cómo se reciben

| Botón | Campo | Requisito |
| --- | --- | --- |
| **Tomar foto** | `<input type="file" accept="image/*" capture="environment">` abre la cámara trasera | CA-17.1 |
| **Galería** | El mismo campo, sin `capture` | CA-17.2 |

`app.js` reduce la foto en el navegador a 1.600 px antes de enviarla, para gastar menos datos en 4G. El servidor la reduce igual, porque no puede confiar en lo que llega.

### Cómo se guardan

`AlmacenLocalPrivado` hace estos pasos, con Intervention Image y el controlador GD:

| Paso | Qué hace | Requisito |
| --- | --- | --- |
| 1. Enderezar | Gira la imagen según cómo estaba el celular al tomarla | — |
| 2. Reducir | Deja el lado mayor en 1.600 px como máximo, sin agrandar las fotos pequeñas | RNF-03 |
| 3. Codificar | JPEG con calidad 80. Si pesa más de 400 KB, baja la calidad de 10 en 10 hasta 50; si aún pesa más, reduce el lado mayor a 1.200 px y repite. Como resguardo sigue con 900, 600 y 400 px, porque la columna `bytes` no admite más de 400 KB | RNF-03 |
| 4. Quitar metadatos | Al volver a codificar con GD se pierden los datos EXIF, incluida la ubicación GPS del celular | RNF-26 |
| 5. Guardar | En el disco `privado` (`storage/app/privado`), con la ruta `fotos/{negocio_id}/{uuid}.jpg` | RNF-25 |
| 6. Registrar | Una fila en `fotos` con la primera posición libre de 1 a 3, la ruta, el ancho, el alto y el peso | RN-17 |

- **Archivo y fila:** el archivo se guarda antes que la fila. Si la fila falla, se borra el archivo.
- **Al eliminar:** una foto o una prenda se borran de la base primero, y sus archivos después de confirmar la transacción.

### Cómo se entregan

- `FotoController@mostrar` responde con el archivo cuya ubicación devuelve `AlmacenDeFotos::entregar()`, con `Content-Type: image/jpeg`.
- Como toda página con datos del taller, sale con `Cache-Control: no-store, private`: al cerrar sesión, el navegador no conserva las fotos (CA-01.4). Se prefirió eso a guardarlas una hora en el caché.
- Nunca se crea el enlace `public/storage` de Laravel, ni hay una dirección pública de las fotos (RNF-25).

## Reloj

- `RelojDeColombia::ahora()` devuelve un `DateTimeImmutable` en la zona `America/Bogota`.
- `hoy()` devuelve ese mismo día a las 00:00.
- En las pruebas se reemplaza por `RelojFijo`.

## Enlace de las interfaces

`AppServiceProvider` decide qué implementación recibe cada caso de uso:

| Interfaz | Implementación | En las pruebas |
| --- | --- | --- |
| `CanalDeAviso` | `EvolutionApiCanal` si Evolution API está configurada; si no, `WhatsAppCloudApiCanal` (ADR-007). `AvisoController` usa `WhatsAppAsistidoCanal` directamente para armar el enlace | `CanalDeAvisoFalso` |
| `AlmacenDeFotos` | `AlmacenLocalPrivado` | La misma clase, sobre un disco falso |
| `Reloj` | `RelojDeColombia`, una sola instancia | `RelojFijo` |
