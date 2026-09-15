<?php

namespace App\Http\Solicitudes;

use App\Modelos\MetodoPago;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de un pago (RN-25). Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 * Que no supere el saldo lo revisa RegistrarPago al guardar (RN-28).
 */
class PagoRequest extends FormRequest
{
    /**
     * Quita el signo de pesos, los puntos de miles y los espacios: «$10.000» es 10000 (RN-11).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('valor'))) {
            $this->merge(['valor' => str_replace(['$', '.', ' '], '', $this->input('valor'))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'token_formulario' => ['required', 'uuid'],
            'valor' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'metodo_pago_id' => ['required', function (string $atributo, mixed $valor, Closure $fallar): void {
                // RN-01: un método activo del negocio de la sesión; el filtro global deja fuera los de otros negocios
                $esDelNegocio = is_scalar($valor) && ctype_digit((string) $valor)
                    && MetodoPago::whereKey((int) $valor)->where('activo', true)->exists();
                if (! $esDelNegocio) {
                    $fallar('Elige cómo pagó.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token_formulario.required' => 'La página se desactualizó. Vuelve a abrir el formulario.',
            'token_formulario.uuid' => 'La página se desactualizó. Vuelve a abrir el formulario.',
            'valor.required' => 'Escribe el valor del pago.',
            'valor.integer' => 'Escribe el valor en pesos, sin centavos.',
            'valor.min' => 'El valor debe ser mayor que cero.',
            'valor.max' => 'Escribe el valor en pesos, sin centavos.',
            'metodo_pago_id.required' => 'Elige cómo pagó.',
        ];
    }
}
