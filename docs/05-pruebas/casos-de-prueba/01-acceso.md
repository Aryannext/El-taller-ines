# Casos de prueba · EP-01 · Acceso

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 4 historias · 17 casos · 16 automáticos · 1 automáticos y manuales · 0 manuales.

## HU-01 · Iniciar y cerrar sesión

**Prioridad:** Must · **Reglas:** RN-01 · **Calidad:** RNF-20, RNF-21 · **Clase de prueba:** `Feature/Http/SesionControllerTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-01.1** Acceso correcto | Dado que tengo un usuario con contraseña válida, cuando inicio sesión con esos datos | entro al panel del día de mi negocio | Funcionalidad | Automática | `test_ca_01_1_acceso_correcto` |
| **CA-01.2** Datos incorrectos | Dado que escribo mal la contraseña, cuando intento iniciar sesión | veo "Usuario o contraseña incorrectos", sin que diga cuál de los dos falló, y no entro | Funcionalidad | Automática | `test_ca_01_2_datos_incorrectos` |
| **CA-01.3** Intentos repetidos | Dado que fallé 5 veces en el último minuto, cuando lo intento por sexta vez | el sistema me pide esperar antes de volver a intentarlo, aunque esta vez la contraseña sea correcta | Funcionalidad | Automática | `test_ca_01_3_intentos_repetidos` |
| **CA-01.4** Cerrar sesión | Dado que tengo la sesión iniciada, cuando cierro la sesión y uso el botón Atrás del navegador | veo la pantalla de inicio de sesión y ningún dato del taller | Funcionalidad | Automática y manual | `test_ca_01_4_cerrar_sesion`<br>y [PM-05](../pruebas-manuales/PM-05-pantallas-y-navegadores.md) |
| **CA-01.5** Sesión abandonada | Dado que dejé la sesión abierta sin usarla durante más de 8 horas, cuando vuelvo a usar el sistema | me pide iniciar sesión de nuevo | Funcionalidad | Automática | `test_ca_01_5_sesion_abandonada` |

## HU-38 · Ponerle a mi taller su nombre, y el mío

**Prioridad:** Should · **Reglas:** RN-01, RN-46, RN-47 · **Calidad:** RNF-09, RNF-12 · **Clase de prueba:** `Feature/Configuracion/PersonalizarTallerTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-38.1** El nombre de mi taller | Dado que mi negocio se llama «Modistería Inés», cuando lo escribo en Ajustes y guardo | lo veo en la pantalla Hoy, y los avisos que reciben mis clientes dicen que les escriben de «Modistería Inés» | Funcionalidad | Automática | `test_ca_38_1_el_nombre_de_mi_taller` |
| **CA-38.2** Mi nombre | Dado que el sistema me dice «Dueña del taller», cuando escribo «Inés» en Ajustes y guardo | el saludo me llama por mi nombre | Funcionalidad | Automática | `test_ca_38_2_mi_nombre` |
| **CA-38.3** El saludo con la hora | Dado que entro a las 2:30 de la tarde, cuando abro la pantalla Hoy | leo «Buenas tardes, Inés», y sería «Buenos días» antes de mediodía y «Buenas noches» desde las 7 | Funcionalidad | Automática | `test_ca_38_3_el_saludo_con_la_hora` |
| **CA-38.4** Sin dejarlo en blanco | Dado que borro el nombre del taller y guardo, cuando el sistema no lo acepta | veo que el taller necesita un nombre, y el anterior se conserva | Funcionalidad | Automática | `test_ca_38_4_sin_dejarlo_en_blanco` |

## HU-37 · Entrar con mi correo de Google

**Prioridad:** Should · **Reglas:** RN-01, RN-45 · **Calidad:** RNF-19, RNF-20 · **Clase de prueba:** `Feature/Acceso/EntrarConGoogleTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-37.1** Correo registrado | Dado que mi correo de Google quedó registrado al instalar el sistema, cuando toco «Entrar con Google» y elijo mi cuenta | entro al panel del día de mi negocio, sin escribir contraseña | Funcionalidad | Automática | `test_ca_37_1_correo_registrado` |
| **CA-37.2** Correo desconocido | Dado que entro con una cuenta de Google que nadie registró, cuando vuelvo del inicio de sesión de Google | no entro, veo que ese correo no tiene acceso y no se crea ninguna usuaria ni ningún taller | Funcionalidad | Automática | `test_ca_37_2_correo_desconocido` |
| **CA-37.3** La contraseña sigue sirviendo | Dado que prefiero entrar como siempre, cuando uso mi usuario y mi contraseña | entro igual que antes | Funcionalidad | Automática | `test_ca_37_3_la_contrasena_sigue_sirviendo` |
| **CA-37.4** Sin Google configurado | Dado que el sistema se instaló sin las llaves de Google, cuando abro la pantalla de inicio de sesión | no veo el botón de Google y entro con usuario y contraseña | Funcionalidad | Automática | `test_ca_37_4_sin_google_configurado` |

## HU-02 · Cambiar mi contraseña

**Prioridad:** Must · **Reglas:** — · **Calidad:** RNF-19 · **Clase de prueba:** `Feature/Configuracion/CambiarContrasenaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-02.1** Cambio correcto | Dado que conozco mi contraseña actual, cuando escribo la actual y una nueva de al menos 8 caracteres dos veces igual | la contraseña cambia y la siguiente vez entro con la nueva | Funcionalidad | Automática | `test_ca_02_1_cambio_correcto` |
| **CA-02.2** Contraseña actual incorrecta | Dado que escribo mal la contraseña actual, cuando intento cambiarla | no cambia y veo que la contraseña actual no es correcta | Funcionalidad | Automática | `test_ca_02_2_contrasena_actual_incorrecta` |
| **CA-02.3** Contraseña corta | Dado que la nueva contraseña tiene 6 caracteres, cuando intento guardarla | no cambia y veo que debe tener al menos 8 caracteres | Funcionalidad | Automática | `test_ca_02_3_contrasena_corta` |
| **CA-02.4** No coinciden | Dado que la confirmación es distinta de la nueva contraseña, cuando intento guardarla | no cambia y veo que las dos contraseñas no coinciden | Funcionalidad | Automática | `test_ca_02_4_no_coinciden` |
