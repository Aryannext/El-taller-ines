<?php

namespace App\Http\Solicitudes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * El motivo de anular un pago (RN-31). Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 * La confirmación (confirmacion=si, RNF-10) la revisa PagoController: si falta, no es un error, se vuelve a preguntar.
 */
class AnulacionRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'motivo_anulacion' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo_anulacion.required' => 'Escribe el motivo de la anulación.',
            'motivo_anulacion.string' => 'Escribe el motivo de la anulación.',
            'motivo_anulacion.max' => 'El motivo puede tener hasta 255 caracteres.',
        ];
    }
}
