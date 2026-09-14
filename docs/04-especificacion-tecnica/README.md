# Especificación técnica

**Estado:** borrador · DOC-19 · Sprint 2 · se valida con el instructor (F-04)

## Para qué sirve

Dice cómo se construye y cómo se pone a funcionar el sistema:

- con qué versiones y dependencias;
- qué rutas, formularios y mensajes tiene;
- cómo guarda los datos y cómo habla con WhatsApp, con el disco de fotos y con el reloj;
- cómo se protege;
- cómo se despliega, se respalda y se instala en el celular.

Es la guía de los Sprints 3 y 4, junto con la arquitectura. Cuando el código necesite algo que aquí no está, primero se agrega aquí.

## Qué no repite

| Pregunta | Dónde está |
| --- | --- |
| Qué clase aplica cada regla y qué caso de uso atiende cada historia | [Arquitectura](../03-diseno/arquitectura/README.md) |
| Qué tablas, columnas y restricciones hay | [Modelo de datos](../03-diseno/modelo-de-datos/README.md) |
| Cómo se relacionan las clases, los estados y las secuencias | [Diagramas de diseño](../03-diseno/diagramas/README.md) |
| Cómo se ve cada pantalla | [Mockups](../03-diseno/mockups/README.md) |
| Qué se prueba y cómo | [Plan de pruebas](../05-pruebas/plan-de-pruebas.md) |

## Documentos

| Documento | Qué define |
| --- | --- |
| [1 · Plataforma y dependencias](01-plataforma-y-dependencias.md) | Versiones, extensiones de PHP, paquetes, interfaz sin compilación y configuración de PHP |
| [2 · Rutas](02-rutas.md) | Cada ruta con su controlador, pantalla e historia; protección, parámetros, confirmaciones y doble envío |
| [3 · Validaciones y mensajes](03-validaciones-y-mensajes.md) | Las reglas de cada formulario y el texto exacto de cada mensaje |
| [4 · Datos y modelos](04-datos-y-modelos.md) | Migraciones, seeders, modelos Eloquent, zona horaria y formatos |
| [5 · Avisos, fotos y reloj](05-avisos-fotos-y-reloj.md) | El evento y la cola de los avisos, la API de WhatsApp, el envío asistido, el tratamiento de las fotos y el reloj |
| [6 · Seguridad](06-seguridad.md) | Autenticación, sesión, cabeceras, aislamiento, secretos y datos personales |
| [7 · Despliegue y operación](07-despliegue-y-operacion.md) | Servidor, variables de entorno, Nginx, cola, respaldos, despliegue, app en el celular y monitoreo |
| [8 · Convenciones de código](08-convenciones-de-codigo.md) | Idioma, nombres, estilo, vistas, Git y GitHub Actions |

## Decisiones técnicas

Son decisiones pequeñas y reversibles, que no justifican un ADR. Cada una dice qué se descartó.

| Decisión | Por qué | Alternativa descartada |
| --- | --- | --- |
| **CSS y JavaScript sin compilación** | Los mockups ya tienen la hoja de estilos final. Sin Node en el servidor, la instalación es más corta (RNF-33) | Vite con Tailwind, que exige Node y un paso de compilación en cada despliegue |
| **JavaScript propio, sin librerías** | Solo hace falta para confirmaciones, el campo «Otro», agregar prendas, reducir fotos y evitar el doble toque | Alpine.js o Livewire: otra herramienta que aprender en poco tiempo |
| **Intervention Image para las fotos** | Endereza la foto según cómo se tomó y la reduce con pocas líneas | GD a mano, donde corregir la orientación es código propio que hay que probar |
| **Sesiones y caché en archivos** | Hay un solo servidor, y así no se agregan tablas fuera del modelo de datos | Base de datos o Redis |
| **El número de la orden en la dirección** (`/ordenes/42`) | Es el número escrito en la bolsa, y no revela cuántas órdenes hay en el sistema | El identificador interno de la tabla |
| **Los mensajes viven en cada solicitud** | El mensaje queda junto a la regla que lo produce y se compara con el criterio de aceptación | Un archivo de idioma genérico con mensajes como «El campo es obligatorio» |
| **Respaldos con scripts de shell y rclone** | `mysqldump`, `tar` y `rclone` son herramientas estándar que se entienden leyendo el script | Un paquete de respaldos para Laravel: otra dependencia para algo de pocas líneas |

### Cambios en otros documentos

Escribir esta especificación obligó a ajustar tres detalles del diseño:

- **Estructura de carpetas:** la hoja de estilos y el JavaScript pasan a `public/`, porque no se compilan. Se agregan la página sin conexión, las fuentes, las tareas programadas y la carpeta `despliegue/`.
- **RN-39:** el diagrama de estados del aviso ya decía que un aviso pendiente de envío asistido se descarta cuando la orden deja de estar lista, pero ninguna clase lo hacía. Ahora lo hace `SincronizarEstadoDeOrden`.
- **`MensajeDeAviso`:** la plantilla de WhatsApp necesita los valores por separado, así que el diagrama de clases del dominio suma el método `parametros()`.

## Verificación

`python scripts/verificar_especificacion.py` comprueba que la especificación no contradiga al resto del diseño:

- cada ruta usa un controlador de la arquitectura;
- cada pantalla tiene su ruta y cada historia una ruta con el controlador que le asigna la arquitectura;
- cada campo validado apunta a una columna real, y su largo máximo coincide con el de la columna;
- cada mensaje citado textualmente en un criterio de aceptación existe en la especificación;
- cada modelo usa columnas reales de su tabla;
- cada variable de entorno mencionada está documentada;
- cada código citado existe.

## Pendiente

- Anotar las versiones exactas al crear el proyecto (HT-02) y al preparar el VPS (HT-04).
- El instructor la aprueba junto con el producto terminado (DOC-14).
