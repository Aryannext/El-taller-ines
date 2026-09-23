<?php

namespace App\Http\Solicitudes;

use App\Modelos\TipoPrenda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PT-23 · El plazo sin reclamar (HU-35) y el nombre de un tipo de prenda (HU-16).
 * Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class AjustesRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        if ($this->routeIs('ajustes.plazo')) {
            // RN-35: el mismo rango que admite la base de datos
            return ['dias_sin_reclamar' => ['required', 'integer', 'between:1,365']];
        }

        /** @var TipoPrenda $tipo */
        $tipo = $this->route('tipo');

        return [
            'nombre' => [
                'required', 'string', 'max:60',
                // RN-43: dos tipos del mismo negocio no se llaman igual
                Rule::unique('tipos_prenda', 'nombre')
                    ->where('negocio_id', $this->user()?->negocio_id)
                    ->ignore($tipo->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dias_sin_reclamar.required' => 'El plazo debe estar entre 1 y 365 días.',
            'dias_sin_reclamar.integer' => 'El plazo debe estar entre 1 y 365 días.',
            'dias_sin_reclamar.between' => 'El plazo debe estar entre 1 y 365 días.',
            'nombre.required' => 'Escribe el nombre del tipo de prenda.',
            'nombre.max' => 'El tipo de prenda puede tener hasta 60 caracteres.',
            'nombre.unique' => 'Ya existe un tipo de prenda con ese nombre.',
        ];
    }
}
