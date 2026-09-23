<?php

use App\Http\Controladores\AjustesController;
use App\Http\Controladores\AvisoController;
use App\Http\Controladores\ClienteController;
use App\Http\Controladores\DineroController;
use App\Http\Controladores\FotoController;
use App\Http\Controladores\OrdenController;
use App\Http\Controladores\PagoController;
use App\Http\Controladores\PanelController;
use App\Http\Controladores\PrendaController;
use App\Http\Controladores\SeguimientoController;
use App\Http\Controladores\SesionController;
use Illuminate\Support\Facades\Route;

// Rutas según docs/04-especificacion-tecnica/02-rutas.md. El enlace de {foto} está en AppServiceProvider (RNF-25).

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [SesionController::class, 'formulario'])->name('sesion.formulario');
    Route::post('/entrar', [SesionController::class, 'entrar'])
        ->middleware('throttle:inicio-de-sesion')
        ->name('sesion.entrar');
});

// RNF-26: la política de tratamiento de datos se lee sin iniciar sesión
Route::view('/politica-de-datos', 'pantallas.politica-de-datos')->name('politica-de-datos');

// Las páginas con datos del taller no se guardan en el navegador: Atrás no las muestra al salir (CA-01.4)
Route::middleware(['auth', 'auth.session', 'cache.headers:no_store;private'])->group(function () {
    Route::post('/salir', [SesionController::class, 'salir'])->name('sesion.salir');

    Route::get('/', [PanelController::class, 'mostrar'])->name('panel');

    // HU-33 y HU-34: las dos listas que extienden el panel (CA-32.2)
    Route::get('/seguimiento/atrasadas', [SeguimientoController::class, 'atrasadas'])->name('seguimiento.atrasadas');
    Route::get('/seguimiento/sin-reclamar', [SeguimientoController::class, 'sinReclamar'])->name('seguimiento.sin-reclamar');

    Route::get('/clientes', [ClienteController::class, 'buscar'])->name('clientes.buscar');
    Route::get('/clientes/nuevo', [ClienteController::class, 'nuevo'])->name('clientes.nuevo');
    Route::post('/clientes', [ClienteController::class, 'guardar'])->name('clientes.guardar');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'ficha'])->whereNumber('cliente')->name('clientes.ficha');
    Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'editar'])->whereNumber('cliente')->name('clientes.editar');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'corregir'])->whereNumber('cliente')->name('clientes.corregir');

    Route::get('/ordenes', [OrdenController::class, 'listar'])->name('ordenes.listar');
    Route::get('/ordenes/nueva', [OrdenController::class, 'nueva'])->name('ordenes.nueva');
    Route::post('/ordenes', [OrdenController::class, 'guardar'])->name('ordenes.guardar');
    Route::get('/ordenes/{orden}/guardada', [OrdenController::class, 'guardada'])->whereNumber('orden')->name('ordenes.guardada');
    Route::get('/ordenes/{orden}', [OrdenController::class, 'detalle'])->whereNumber('orden')->name('ordenes.detalle');
    Route::get('/ordenes/{orden}/entregar', [OrdenController::class, 'confirmarEntrega'])->whereNumber('orden')->name('ordenes.confirmar-entrega');
    Route::post('/ordenes/{orden}/entregar', [OrdenController::class, 'entregar'])->whereNumber('orden')->name('ordenes.entregar');
    Route::get('/ordenes/{orden}/cancelar', [OrdenController::class, 'confirmarCancelacion'])->whereNumber('orden')->name('ordenes.confirmar-cancelacion');
    Route::post('/ordenes/{orden}/cancelar', [OrdenController::class, 'cancelar'])->whereNumber('orden')->name('ordenes.cancelar');

    // HU-11: la prenda que se olvidó registrar al recibir la orden
    Route::get('/ordenes/{orden}/prendas/nueva', [PrendaController::class, 'nueva'])->whereNumber('orden')->name('prendas.nueva');
    Route::post('/ordenes/{orden}/prendas', [PrendaController::class, 'agregar'])->whereNumber('orden')->name('prendas.agregar');

    // La prenda y el pago se buscan dentro de la orden de la dirección: /ordenes/43/prendas/7 no abre una prenda de la #0042
    Route::scopeBindings()->group(function () {
        // RN-19: el estado se cambia en la prenda; no hay ruta para cambiar el de la orden
        Route::get('/ordenes/{orden}/prendas/{prenda}', [PrendaController::class, 'acciones'])->whereNumber(['orden', 'prenda'])->name('prendas.acciones');
        Route::post('/ordenes/{orden}/prendas/{prenda}/estado', [PrendaController::class, 'cambiarEstado'])->whereNumber(['orden', 'prenda'])->name('prendas.cambiar-estado');
        Route::get('/ordenes/{orden}/prendas/{prenda}/editar', [PrendaController::class, 'editar'])->whereNumber(['orden', 'prenda'])->name('prendas.editar');
        Route::put('/ordenes/{orden}/prendas/{prenda}', [PrendaController::class, 'corregir'])->whereNumber(['orden', 'prenda'])->name('prendas.corregir');
        // HU-13: la prenda que se registró por error. Sin la confirmación del cuadro de diálogo no se borra nada (RNF-10)
        Route::delete('/ordenes/{orden}/prendas/{prenda}', [PrendaController::class, 'eliminar'])->whereNumber(['orden', 'prenda'])->name('prendas.eliminar');
        Route::post('/ordenes/{orden}/prendas/{prenda}/fotos', [FotoController::class, 'guardar'])->whereNumber(['orden', 'prenda'])->name('fotos.agregar');

        // HU-36: el cliente se lleva la prenda sin arreglar. Se confirma antes, porque no se deshace (RN-44, RNF-10)
        Route::get('/ordenes/{orden}/prendas/{prenda}/devolver', [PrendaController::class, 'confirmarDevolucion'])->whereNumber(['orden', 'prenda'])->name('prendas.confirmar-devolucion');
        Route::post('/ordenes/{orden}/prendas/{prenda}/devolver', [PrendaController::class, 'devolver'])->whereNumber(['orden', 'prenda'])->name('prendas.devolver');

        // RN-31: un pago se anula, no se borra; no hay ruta para eliminarlo
        Route::get('/ordenes/{orden}/pagos/{pago}/anular', [PagoController::class, 'confirmarAnulacion'])->whereNumber(['orden', 'pago'])->name('pagos.confirmar-anulacion');
        Route::post('/ordenes/{orden}/pagos/{pago}/anular', [PagoController::class, 'anular'])->whereNumber(['orden', 'pago'])->name('pagos.anular');
    });

    Route::get('/ordenes/{orden}/fotos', [FotoController::class, 'deOrden'])->whereNumber('orden')->name('fotos.de-orden');
    Route::get('/fotos/{foto}', [FotoController::class, 'mostrar'])->whereNumber('foto')->name('fotos.mostrar');
    // HU-19: la foto borrosa o equivocada. Sin la confirmación del cuadro de diálogo no se borra nada (RNF-10)
    Route::delete('/fotos/{foto}', [FotoController::class, 'eliminar'])->whereNumber('foto')->name('fotos.eliminar');

    Route::get('/ordenes/{orden}/pagos/nuevo', [PagoController::class, 'nuevo'])->whereNumber('orden')->name('pagos.nuevo');
    Route::post('/ordenes/{orden}/pagos', [PagoController::class, 'guardar'])->whereNumber('orden')->name('pagos.guardar');

    // HU-26 y HU-27: quién me debe y cuánto he recibido
    Route::get('/dinero', [DineroController::class, 'mostrar'])->name('dinero');

    // HU-29: los avisos que la dueña envía desde su WhatsApp. El enlace de {aviso} está en AppServiceProvider (RNF-25)
    Route::get('/avisos', [AvisoController::class, 'pendientes'])->name('avisos.pendientes');
    Route::get('/avisos/{aviso}/whatsapp', [AvisoController::class, 'abrirWhatsapp'])->whereNumber('aviso')->name('avisos.abrir-whatsapp');
    Route::post('/avisos/{aviso}/enviado', [AvisoController::class, 'confirmarEnvio'])->whereNumber('aviso')->name('avisos.confirmar-envio');

    Route::get('/ajustes', [AjustesController::class, 'mostrar'])->name('ajustes');
    Route::put('/ajustes/contrasena', [AjustesController::class, 'cambiarContrasena'])->name('ajustes.contrasena');
});
