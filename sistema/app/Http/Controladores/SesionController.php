<?php

namespace App\Http\Controladores;

use App\Aplicacion\Acceso\EntrarConGoogle;
use App\Dominio\Compartido\ReglaIncumplida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * HU-01 · Iniciar y cerrar sesión (RF-01, RF-02).
 */
class SesionController
{
    public function formulario(EntrarConGoogle $entrarConGoogle): View
    {
        // CA-37.4: sin las llaves de Google no hay botón, y se entra con usuario y contraseña
        return view('pantallas.pt-01-iniciar-sesion', ['hayGoogle' => $entrarConGoogle->estaDisponible()]);
    }

    /**
     * HU-37 · Manda a la usuaria a Google. El «estado» queda en su sesión: a la vuelta se compara, y así
     * una dirección de vuelta fabricada por otro no sirve para nada.
     */
    public function irAGoogle(Request $request, EntrarConGoogle $entrarConGoogle): RedirectResponse
    {
        if (! $entrarConGoogle->estaDisponible()) {
            return redirect()->route('sesion.formulario');
        }

        $estado = Str::random(40);
        $request->session()->put('google_estado', $estado);

        return redirect()->away($entrarConGoogle->direccionDeGoogle($estado));
    }

    /**
     * La vuelta de Google. Solo entra un correo ya registrado (RN-45): aquí no se crea ninguna usuaria.
     */
    public function volverDeGoogle(Request $request, EntrarConGoogle $entrarConGoogle): RedirectResponse
    {
        $esperado = $request->session()->pull('google_estado');
        $codigo = $request->query('code');

        if (! is_string($esperado) || $request->query('state') !== $esperado || ! is_string($codigo)) {
            // La usuaria canceló en Google, o la vuelta no corresponde a esta sesión
            return redirect()->route('sesion.formulario');
        }

        try {
            $usuaria = $entrarConGoogle->usuariaDelCodigo($codigo);
        } catch (ReglaIncumplida $regla) {
            throw ValidationException::withMessages(['usuario' => $regla->mensajeParaUsuaria]);
        }

        Auth::login($usuaria);
        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
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
