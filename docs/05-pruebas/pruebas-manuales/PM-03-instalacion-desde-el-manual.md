# PM-03 · Instalación siguiendo el manual

**Verifica:** RNF-33 · **Cuándo:** Cierre, con el manual técnico (DOC-23) · **Entorno:** una máquina distinta a la de desarrollo

## Objetivo

Comprobar que alguien instala el sistema desde el repositorio en 30 minutos o menos siguiendo solo el manual técnico.

## Participantes

- **Quien instala:** de preferencia un compañero de formación. Si no hay nadie disponible, lo hace el aprendiz y se declara en el informe, porque conoce pasos que el manual podría omitir.
- **Observador:** el aprendiz. No responde preguntas; anota cada duda.

## Preparación

1. La máquina ya tiene PHP 8.4, Composer, MySQL 8.4 y Git. Instalarlos no cuenta en el tiempo, porque RNF-33 los da por instalados.
2. La máquina nunca ha tenido el proyecto.
3. Quien instala tiene acceso de lectura al repositorio.
4. Se entrega solo el enlace al manual técnico.

## Pasos

1. Anotar el sistema operativo y las versiones de PHP, Composer y MySQL.
2. **Iniciar el cronómetro.**
3. Seguir el manual: clonar, instalar dependencias, configurar `.env`, crear la base, migrar y cargar los datos iniciales.
4. Cada vez que quien instala duda, se equivoca o hace algo que el manual no dice, anotarlo con el paso del manual.
5. **Detener el cronómetro** cuando quien instala inicia sesión y ve el panel del día.
6. Correr `php artisan test`. Todas las pruebas deben pasar en esa máquina.

## Criterio de aprobación

- El panel del día abre en 30 minutos o menos, sin ayuda del observador.
- Las pruebas pasan en la máquina nueva.

Cada duda anotada se corrige en el manual, aunque la prueba se apruebe.

## Ensayo previo

**No es el registro de la prueba:** fue en la misma máquina de desarrollo y lo hizo quien escribió el manual. Sirvió para corregir el manual antes de que lo siga otra persona.

22 de septiembre de 2026, commit `7c58cf6`, Windows 11 con PHP 8.4.25, Composer 2 y un MySQL 8.4.11 recién inicializado, sin bases ni usuarios. Se siguieron los pasos del manual al pie de la letra, desde el clon de GitHub:

| Paso | Qué pasó | Corrección al manual |
| --- | --- | --- |
| 1 · Clonar | En una carpeta profunda, Git falló con «Filename too long» y no dejó el código: Windows limita las rutas a 260 caracteres | Clonar en una ruta corta, o `git config --global core.longpaths true`. Quedó en el paso 1 y en solución de problemas |
| 2 · Composer | 12 minutos: descargó todo porque no había caché | Se avisa en el paso 2 que es el más largo |
| 3 a 8 | Sin dudas: la llave, las bases, `.env`, las migraciones y los dos seeders funcionaron como dice el manual | — |
| 9 · Entrar | El panel **Hoy** abrió a los 12 minutos 35 segundos del inicio | — |
| 10 · Pruebas | 248 pruebas pasaron en 20 segundos | — |

## Registro

**Fecha:** · **Quien instala:** · **Sistema operativo:** · **PHP / Composer / MySQL:** · **Commit:**

**Hora de inicio:** · **Hora de fin:** · **Duración:** · **Resultado de las pruebas:**

| Paso del manual | Qué pasó | Corrección al manual |
| --- | --- | --- |
| | | |

**Resultado:**
