# Casos de prueba · EP-04 · Identificación de prendas

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 3 historias · 10 casos · 7 automáticos · 1 automáticos y manuales · 2 manuales.

## HU-17 · Tomar fotos de las prendas

**Prioridad:** Must · **Reglas:** RN-17 · **Calidad:** RNF-03 · **Clase de prueba:** `Feature/Fotos/AgregarFotoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-17.1** Con la cámara | Dado que estoy registrando un vestido desde el celular, cuando le tomo una foto con la cámara | la foto queda asociada al vestido | Sistema | Manual | [PM-04](../pruebas-manuales/PM-04-app-en-el-celular.md) |
| **CA-17.2** Desde la galería | Dado que estoy registrando un vestido, cuando elijo una foto de la galería | la foto queda asociada al vestido | Funcionalidad | Automática y manual | `test_ca_17_2_desde_la_galeria`<br>y [PM-04](../pruebas-manuales/PM-04-app-en-el-celular.md) |
| **CA-17.3** Máximo tres | Dado que el vestido ya tiene 3 fotos, cuando intento agregar una cuarta | no se permite | Funcionalidad | Automática | `test_ca_17_3_maximo_tres` |
| **CA-17.4** Sin foto | Dado que tengo prisa, cuando guardo el vestido sin foto | se guarda y el sistema me sugiere tomarle una foto | Funcionalidad | Automática | `test_ca_17_4_sin_foto` |
| **CA-17.5** Foto pesada | Dado que la foto pesa 5 MB, cuando la agrego | se guarda reducida a 1.600 px en su lado mayor y a no más de 400 KB | Funcionalidad | Automática | `test_ca_17_5_foto_pesada` |

## HU-18 · Ver las fotos de una orden para reconocer las prendas

**Prioridad:** Must · **Reglas:** RN-17 · **Calidad:** RNF-25 · **Clase de prueba:** `Feature/Consultas/FotosDeOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-18.1** Fotos agrupadas | Dado que la #0042 tiene un pantalón con 2 fotos y una camisa con 1, cuando abro las fotos de la orden | veo las 3 fotos agrupadas por prenda, con su tipo y su descripción | Funcionalidad | Automática | `test_ca_18_1_fotos_agrupadas` |
| **CA-18.2** Ampliar | Dado que estoy viendo las fotos de la orden, cuando toco una foto | se ve ampliada | Sistema | Manual | [PM-05](../pruebas-manuales/PM-05-pantallas-y-navegadores.md) |
| **CA-18.3** Fotos privadas | Dado que alguien sin sesión tiene el enlace de una foto, cuando lo abre | no ve la foto | Funcionalidad | Automática | `test_ca_18_3_fotos_privadas` |

## HU-19 · Eliminar una foto

**Prioridad:** Should · **Reglas:** RN-17 · **Calidad:** RNF-10 · **Clase de prueba:** `Feature/Fotos/EliminarFotoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-19.1** Libera espacio para otra | Dado que el vestido tiene 3 fotos, cuando elimino una y lo confirmo | quedan 2 y puedo agregar otra | Funcionalidad | Automática | `test_ca_19_1_libera_espacio_para_otra` |
| **CA-19.2** La prenda se conserva | Dado que el vestido tiene 1 foto, cuando la elimino y lo confirmo | el vestido sigue registrado, sin fotos | Funcionalidad | Automática | `test_ca_19_2_la_prenda_se_conserva` |
