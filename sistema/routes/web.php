<?php

use App\Http\Controladores\AjustesController;
use App\Http\Controladores\ClienteController;
use App\Http\Controladores\FotoController;
use App\Http\Controladores\OrdenController;
use App\Http\Controladores\PanelController;
use App\Http\Controladores\PrendaController;
use App\Http\Controladores\SesionController;
use Illuminate\Support\Facades\Route;

// Rutas según docs/04-especificacion-tecnica/02-rutas.md

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [SesionController::class, 'formulario'])->name('sesion.formulario');
    Route::post('/entrar', [SesionController::class, 'entrar'])
        ->middleware('throttle:inicio-de-sesion')
        ->name('sesion.entrar');
});

// Las páginas con datos del taller no se guardan en el navegador: Atrás no las muestra al salir (CA-01.4)
Route::middleware(['auth', 'auth.session', 'cache.headers:no_store;private'])->group(function () {
    Route::post('/salir', [SesionController::class, 'salir'])->name('sesion.salir');

    Route::get('/', [PanelController::class, 'mostrar'])->name('panel');

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

    // La prenda se busca dentro de la orden de la dirección: /ordenes/43/prendas/7 no abre una prenda de la #0042
    Route::scopeBindings()->group(function () {
        Route::get('/ordenes/{orden}/prendas/{prenda}/editar', [PrendaController::class, 'editar'])->whereNumber(['orden', 'prenda'])->name('prendas.editar');
        Route::put('/ordenes/{orden}/prendas/{prenda}', [PrendaController::class, 'corregir'])->whereNumber(['orden', 'prenda'])->name('prendas.corregir');
    });

    Route::get('/fotos/{foto}', [FotoController::class, 'mostrar'])->whereNumber('foto')->name('fotos.mostrar');

    Route::get('/ajustes', [AjustesController::class, 'mostrar'])->name('ajustes');
    Route::put('/ajustes/contrasena', [AjustesController::class, 'cambiarContrasena'])->name('ajustes.contrasena');
});
