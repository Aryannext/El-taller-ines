<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('sesion.formulario'));
        $middleware->redirectUsersTo(fn () => route('panel'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel solo deja fuera de la sesión los campos llamados «password»; aquí se llaman en español
        $exceptions->dontFlash(['contrasena', 'contrasena_actual', 'contrasena_nueva', 'contrasena_nueva_confirmation']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
