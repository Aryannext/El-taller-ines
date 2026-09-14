# Convenciones de código

## Idioma

| Qué | Idioma | Ejemplo |
| --- | --- | --- |
| **Clases, métodos, variables, tablas, rutas y vistas** | Español, sin tildes ni eñes en los identificadores | `RegistrarPago`, `$saldoPendiente`, `tipos_prenda`, `contrasena` |
| **Lo que exigen PHP o Laravel** | Inglés | `handle()`, `casts()`, `register()`, `$fillable` |
| **Textos para la usuaria** | Español correcto, con tildes | «Escribe el nombre del cliente.» |
| **Comentarios** | Español, solo cuando el porqué no es evidente en el código | — |
| **Mensajes de commit** | Español | — |

Los términos son los del [glosario](../02-requisitos/reglas-de-negocio.md#glosario): orden, prenda, abono, saldo, aviso. No se mezclan sinónimos como pedido, ítem o notificación.

## Nombres

| Elemento | Forma | Ejemplo |
| --- | --- | --- |
| **Clase** | PascalCase; los casos de uso empiezan con un verbo | `CalculadoraDeSaldo`, `RegistrarOrden` |
| **Método** | camelCase y con verbo | `ejecutar`, `exigirValorNoMenorQuePagado` |
| **Variable y propiedad** | camelCase | `$fechaEntregaAcordada` |
| **Tabla** | snake_case, en plural | `metodos_pago` |
| **Columna** | snake_case. Fecha con hora termina en `_en`; fecha sin hora empieza por `fecha_` | `anulado_en`, `fecha_entrega_acordada` |
| **Ruta** | Minúsculas con guiones; su nombre, con puntos | `/seguimiento/sin-reclamar`, `seguimiento.sin-reclamar` |
| **Vista** | El nombre del archivo de su mockup | `pantallas/pt-09-detalle-orden.blade.php` |
| **Prueba** | El código del criterio, regla o requisito que prueba | `test_ca_23_3_supera_el_saldo` |
| **Migración** | Verbo y tabla | `2026_09_29_120000_crear_tabla_ordenes.php` |

## Estilo

- **Formato:** PSR-12 con Laravel Pint y su preset `laravel`, configurado en `sistema/pint.json` (RNF-29).
- **Análisis estático:** Larastan en nivel 5 o superior, sin errores (RNF-29).
- **Tipos estrictos:** `declare(strict_types=1);` en las clases de `app/Dominio` y `app/Aplicacion`.
- **Tipos declarados:** siempre en parámetros, retornos y propiedades. Los objetos de valor usan propiedades `readonly`.
- **Casos de uso:** un método público, `ejecutar()`. Las consultas usan un método con el nombre de lo que devuelven, como `listar()`.
- **Controladores delgados:** validan, llaman un caso de uso o una consulta, y devuelven una vista o una redirección. No calculan ni consultan modelos (RNF-27).
- **Dinero:** siempre `int` o `Dinero`, nunca `float` (RN-11).
- **Fechas:** siempre desde `Reloj` (RN-09).
- **Errores del dominio:** `ReglaIncumplida`. El dominio no llama `abort()` ni conoce los códigos HTTP.
- **Variables de entorno:** `env()` solo dentro de `config/`.

## Vistas

- **Imprimir datos:** siempre con `{{ }}`. `{!! !!}` está prohibido (RNF-23).
- **Estilos:** sin estilos en línea; todo va en clases de `estilos.css`, por la política de seguridad de contenido.
- **Accesibilidad:** cada campo tiene su `label for`, y un campo con error lleva `aria-invalid` y `aria-describedby` apuntando a su mensaje, como en los mockups (RNF-11).
- **Componentes:** lo que se repite en más de una pantalla es un componente de `resources/views/components/`.

## Git

- **Una sola rama, `main`**, con cambios pequeños y frecuentes. GitHub Actions valida cada envío (RNF-30).
- **Mensaje:** `tipo(ámbito): descripción en minúscula`. El cuerpo explica el porqué cuando no es evidente.
- **Asistencia de IA:** cuando un asistente de IA participó en el cambio, el commit lo declara con `Co-Authored-By`, como se declara en el plan de sprints.
- **Nunca se suben** `.env`, llaves, APK, respaldos ni fotos.

| Tipo | Cuándo | Ámbito | Ejemplo |
| --- | --- | --- | --- |
| `feat` | Una historia o una parte de ella | Código de la historia | `feat(HU-07): registrar una orden con sus prendas` |
| `fix` | Corregir un defecto | Número del issue | `fix(#75): el saldo no descuenta los pagos anulados` |
| `test` | Solo pruebas | Código de la historia o regla | `test(RN-28): pago que supera el saldo` |
| `refactor` | Ordenar el código sin cambiar su comportamiento | Capa o módulo | `refactor(pagos): extraer la consulta de saldo` |
| `docs` | Documentación | Sprint o documento | `docs(sprint-2): especificación técnica` |
| `chore` | Configuración, dependencias o despliegue | Habilitador | `chore(HT-04): servicio de la cola` |

## GitHub Actions

HT-02 crea el flujo de trabajo `.github/workflows/calidad.yml`. Corre en cada envío y marca en rojo si un paso falla:

| Paso | Qué hace | Requisito |
| --- | --- | --- |
| 1 | Prepara PHP 8.4, con las extensiones de [plataforma](01-plataforma-y-dependencias.md#extensiones-de-php) y PCOV, y un servicio de MySQL 8.4 | — |
| 2 | `composer install` | — |
| 3 | `composer audit` | RNF-23 |
| 4 | gitleaks | RNF-24 |
| 5 | `vendor/bin/pint --test` | RNF-29 |
| 6 | `vendor/bin/phpstan analyse`, con Larastan y las reglas de PHPat | RNF-27, RNF-29 |
| 7 | Busca `{!!` en `resources/views` y falla si lo encuentra | RNF-23 |
| 8 | `php artisan migrate:fresh` y comparación del esquema con `esquema.sql` | RNF-31 |
| 9 | `php artisan test --coverage --min=80`, con la cobertura limitada a `app/Dominio` y `app/Aplicacion` en `phpunit.xml` | RNF-28 |
