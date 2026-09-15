<?php

namespace App\Http\Solicitudes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU-02 · Cambiar mi contraseña (RF-03, RNF-19). Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class ContrasenaRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'contrasena_actual' => ['required', 'current_password'],
            // bcrypt ignora lo que pase de 72 caracteres
            'contrasena_nueva' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contrasena_actual.required' => 'Escribe tu contraseña actual.',
            'contrasena_actual.current_password' => 'La contraseña actual no es correcta.',
            'contrasena_nueva.required' => 'Escribe la nueva contraseña.',
            'contrasena_nueva.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'contrasena_nueva.max' => 'La contraseña puede tener hasta 72 caracteres.',
            'contrasena_nueva.confirmed' => 'Las dos contraseñas no coinciden.',
        ];
    }
}
