<?php

namespace App\Http\Controladores;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * HU-01 · Iniciar y cerrar sesión (RF-01, RF-02).
 */
class SesionController
{
    public function formulario(): View
    {
        return view('pantallas.pt-01-iniciar-sesion');
    }

    public function entrar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:60'],
            'contrasena' => ['required', 'string'],
        ], [
            'usuario.required' => 'Escribe tu usuario.',
            'usuario.max' => 'Usuario o contraseña incorrectos.',
            'contrasena.required' => 'Escribe tu contraseña.',
        ]);

        // La clave del arreglo es «password» aunque la columna sea «contrasena» (Usuario::$authPasswordName)
        if (! Auth::attempt(['usuario' => $datos['usuario'], 'password' => $datos['contrasena']])) {
            // Un solo mensaje: no revela si falló el usuario o la contraseña (CA-01.2)
            throw ValidationException::withMessages(['usuario' => 'Usuario o contraseña incorrectos.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function salir(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('sesion.formulario');
    }
}
