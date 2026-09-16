# Requisitos no funcionales

**Estado:** borrador · Sprint 1 · se valida con el instructor (F-04)

## Cómo están escritos

Un requisito no funcional dice **qué tan bien** debe hacer el sistema lo que piden los requisitos funcionales. Para que se pueda comprobar, cada uno tiene:

- **Métrica:** qué se mide.
- **Meta:** el valor que se debe alcanzar.
- **Verificación:** cómo y con qué se comprueba.

Se agrupan según las características de calidad del producto de la norma **ISO/IEC 25010**. La adecuación funcional la cubren los [requisitos funcionales](requisitos-funcionales.md).

### Volumen de referencia

Las metas de desempeño se fijan con el volumen que tendría un negocio después de **3 años de uso**, calculado desde la magnitud del problema (F-05):

| Dato | Cálculo | Volumen de prueba |
| --- | --- | --- |
| Prendas | 60 prendas al mes × 36 meses | 2.200 |
| Órdenes | 3 prendas por orden en promedio | 750 |
| Clientes | Clientes que vuelven con varias órdenes | 500 |
| Fotos | Hasta 3 por prenda | 6.600 |
| Pagos | 2 por orden en promedio | 1.500 |

**Resumen:** 35 requisitos · desempeño 4 · compatibilidad 2 · usabilidad 6 · fiabilidad 5 · seguridad 9 · mantenibilidad 6 · portabilidad 3.

---

## Eficiencia de desempeño

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-01** | Las pantallas de uso diario responden rápido con el volumen de 3 años | Tiempo de respuesta del servidor del panel, detalle de orden, búsqueda de clientes y listas de seguimiento | Percentil 95 ≤ 500 ms en el VPS; página utilizable en ≤ 3 s en una red 4G simulada | Datos de prueba con el volumen de referencia; medición del servidor y de las herramientas del navegador |
| **RNF-02** | El número de consultas a la base de datos no crece con la cantidad de prendas | Consultas SQL por página | ≤ 15 por página, sin importar si la orden tiene 1 o 10 prendas | Prueba automática que cuenta las consultas con órdenes de 1 y de 10 prendas (F-02: la versión 1 hacía consultas repetidas por cada prenda) |
| **RNF-03** | Las fotos se reducen antes de guardarse | Tamaño y resolución de cada foto guardada | Lado mayor ≤ 1.600 px y ≤ 400 KB | Prueba automática que sube una foto de 5 MB y revisa lo guardado |
| **RNF-04** | Registrar una orden o cambiar un estado no espera a WhatsApp | Tiempo de respuesta al marcar la última prenda como Terminada | ≤ 1 s aunque el envío del aviso tarde o falle | Prueba automática con un canal de aviso simulado que tarda 10 s (ADR-003) |

## Compatibilidad

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-05** | El sistema funciona en los navegadores del taller | Funciones de los requisitos Must que operan correctamente | 100 % en Chrome para Android y en Chrome y Edge de escritorio, en sus dos últimas versiones principales | Lista de chequeo manual en cada navegador antes de la entrega |
| **RNF-06** | La integración con WhatsApp pasa por un solo adaptador por canal | Componentes fuera de los adaptadores de canal que llaman a un servicio de WhatsApp | Cero; cambiar de servicio o de versión solo modifica su adaptador | Revisión de código, pruebas de cada adaptador con respuestas simuladas y un envío real por Evolution API (ADR-003, ADR-007) |

## Usabilidad

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-07** | El diseño es primero para el celular | Ancho mínimo sin desplazamiento horizontal y tamaño de los elementos táctiles | Uso completo desde 360 px de ancho; botones y enlaces de al menos 44 × 44 px | Revisión de cada pantalla a 360 px |
| **RNF-08** | Los datos se muestran como se leen en Colombia | Formato de dinero, fechas y horas | Pesos con punto de miles y sin decimales ($15.000); fechas como "14 sep 2026"; hora de 12 horas con a. m. y p. m.; zona horaria de Colombia (RN-09) | Pruebas automáticas de formato |
| **RNF-09** | Los errores dicen qué pasó y cómo corregirlo | Mensajes de validación junto al campo que los causa, en español y sin términos técnicos | 100 % de los mensajes de validación | Revisión de todos los mensajes contra una lista de chequeo |
| **RNF-10** | Las acciones que no se pueden deshacer piden confirmación | Acciones de cancelar orden, anular pago, eliminar prenda y eliminar foto con confirmación previa | 100 % | Pruebas automáticas de cada acción |
| **RNF-11** | El sistema es accesible | Contraste de color y puntaje de accesibilidad | Contraste mínimo de 4,5:1 en textos (WCAG 2.1 nivel AA); puntaje de accesibilidad de Lighthouse ≥ 90 en cada pantalla | Auditoría con Lighthouse en cada pantalla |
| **RNF-12** | Registrar una orden es rápido para alguien que no conoce el sistema | Tiempo para registrar una orden de 3 prendas, sin fotos, después de una demostración de 5 minutos | ≤ 2 minutos en al menos 2 de 3 personas | Prueba de usabilidad con 3 compañeros de formación, con su registro |

## Fiabilidad

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-13** | Las operaciones que tocan varios datos se hacen completas o no se hacen | Registros parciales después de un fallo al guardar una orden con prendas, un pago o una entrega | Cero | Pruebas automáticas que fuerzan un error a mitad de la operación y revisan la base |
| **RNF-14** | Enviar dos veces el mismo formulario no duplica registros | Pagos u órdenes duplicados por doble toque | Cero | Prueba automática que envía dos veces la misma solicitud (F-02) |
| **RNF-15** | La información se respalda y se puede recuperar | Frecuencia, retención y tiempo de restauración de los respaldos de base de datos y fotos | Respaldo diario automático en el VPS, conservado 14 días; una copia semanal automática en Google Drive; restauración completa en ≤ 1 hora | Restauración probada al menos una vez antes de la entrega, con su registro; copia semanal visible en Google Drive |
| **RNF-16** | El sistema está disponible en el horario del taller | Disponibilidad mensual entre 7:00 a. m. y 8:00 p. m. | ≥ 99 % | Monitor externo que revisa el sistema cada 5 minutos desde el despliegue |
| **RNF-17** | Un fallo de WhatsApp no deja al cliente sin aviso | Avisos perdidos cuando la API oficial falla | Cero: 3 reintentos con espera creciente y luego envío asistido (RN-40) | Prueba automática con un canal que siempre falla |

> **Espacio de la copia en Google Drive (RNF-15).** En el peor caso, 60 prendas al mes con 3 fotos de 400 KB suman unos 72 MB de fotos al mes: cerca de 2,6 GB en 3 años, más una base de datos de pocos megabytes. Cabe en los 15 GB gratuitos de Google Drive, que se comparten con el resto de la cuenta donde se guarde.

## Seguridad

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-18** | Toda la comunicación va cifrada | Solicitudes atendidas sin HTTPS | Cero; toda solicitud HTTP se redirige a HTTPS | Revisión de la configuración del servidor y solicitud de prueba por HTTP |
| **RNF-19** | Las contraseñas no se pueden leer | Contraseñas guardadas en texto plano y longitud mínima | Cero en texto plano (se guardan con un algoritmo de hash seguro); mínimo 8 caracteres | Prueba automática del registro y cambio de contraseña |
| **RNF-20** | Se limitan los intentos de adivinar una contraseña | Intentos fallidos de inicio de sesión permitidos | Máximo 5 por minuto por usuario y dirección IP; luego se bloquea temporalmente | Prueba automática con 6 intentos fallidos |
| **RNF-21** | Una sesión abandonada se cierra | Tiempo de inactividad hasta que la sesión expira | 8 horas | Prueba automática de expiración |
| **RNF-22** | Ningún negocio ve ni modifica los datos de otro | Accesos a datos de otro negocio | Cero en todas las rutas de consulta y modificación; pedir un dato de otro negocio responde como si no existiera | Prueba automática con dos negocios que recorre todas las rutas (ADR-002, RN-01) |
| **RNF-23** | El sistema resiste los ataques web más comunes | Hallazgos de severidad alta frente a OWASP Top 10: inyección SQL, XSS, CSRF, control de acceso | Cero | Revisión con lista de chequeo OWASP Top 10 y pruebas automáticas de CSRF y control de acceso |
| **RNF-24** | Las credenciales no están en el código | Secretos (base de datos, API de WhatsApp) en el repositorio | Cero; viven en variables de entorno | Búsqueda automática de secretos en el repositorio |
| **RNF-25** | Las fotos de los clientes no son públicas | Fotos accesibles sin iniciar sesión o desde otro negocio | Cero | Prueba automática que pide una foto sin sesión y desde otro negocio |
| **RNF-26** | Los datos personales se tratan según la ley colombiana | Datos personales solicitados y política de tratamiento | Solo nombre y celular del cliente; política de tratamiento de datos disponible en el sistema (Ley 1581 de 2012) | Revisión de formularios y de la política publicada |

## Mantenibilidad

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-27** | Las reglas de negocio están separadas de la interfaz y de la base de datos | Controladores o vistas que contienen reglas de negocio o consultas directas | Cero; las reglas viven en la capa definida en la arquitectura del diseño | Pruebas automáticas de arquitectura y revisión de código |
| **RNF-28** | Las reglas de negocio están probadas | Reglas con al menos una prueba automática y cobertura de líneas de la lógica de negocio | 44 de 44 reglas; cobertura ≥ 80 % en la lógica de negocio | Reporte de cobertura y matriz regla → prueba |
| **RNF-29** | El código sigue un estilo único y no tiene errores detectables sin ejecutarlo | Errores de estilo (PSR-12 con Laravel Pint) y de análisis estático (Larastan) | Cero errores; Larastan en nivel 5 o superior | Ejecución de las herramientas en cada integración |
| **RNF-30** | Cada cambio se valida automáticamente | Cambios integrados a `main` sin pasar pruebas, estilo y análisis estático | Cero; se ejecutan en GitHub Actions en cada envío | Historial de ejecuciones de GitHub Actions |
| **RNF-31** | La estructura de la base de datos está versionada | Cambios a la base de datos hechos a mano fuera de las migraciones | Cero | Revisión: la base se reconstruye completa solo con las migraciones |
| **RNF-32** | La base de datos está normalizada | Tablas que no cumplen la tercera forma normal sin una decisión documentada | Cero; toda desnormalización tiene su ADR | Documento de normalización del modelo de datos |

## Portabilidad

| Código | Requisito | Métrica | Meta | Verificación |
| --- | --- | --- | --- | --- |
| **RNF-33** | El sistema se instala siguiendo el manual | Tiempo para instalarlo desde el repositorio en una máquina con PHP 8.4, Composer y MySQL 8.4 | ≤ 30 minutos siguiendo solo el manual técnico | Instalación de prueba en una máquina distinta a la de desarrollo, con su registro |
| **RNF-34** | La configuración cambia sin tocar el código | Cambios de código necesarios para pasar de desarrollo a producción | Cero; todo se configura por variables de entorno | Despliegue en el VPS usando el mismo código del repositorio |
| **RNF-35** | El sistema se instala en el celular como una app | Formas de instalarlo y cómo se abre | Un APK firmado se instala en Android y abre el sistema a pantalla completa, sin barra del navegador; en otros celulares se instala desde el navegador; sin conexión muestra una página que lo explica (ADR-006) | Instalación del APK en un celular Android real, toma de una foto y apertura de WhatsApp desde el APK, e instalación desde el navegador en otro celular, con su registro |
