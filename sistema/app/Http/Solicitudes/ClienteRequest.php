<?php

namespace App\Http\Solicitudes;

use App\Dominio\Clientes\Celular;
use App\Dominio\Compartido\ReglaIncumplida;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de un cliente (RN-02, RN-03). Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class ClienteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('celular'))) {
            $this->merge(['celular' => preg_replace('/\s+/', '', $this->input('celular'))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'celular' => ['required', function (string $atributo, mixed $valor, Closure $fallar): void {
                // El mensaje sale del objeto de valor: la regla vive en un solo lugar (RN-03)
                try {
                    Celular::desde(is_string($valor) ? $valor : '');
                } catch (ReglaIncumplida $error) {
                    $fallar($error->mensajeParaUsuaria);
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
            'nombre.required' => 'Escribe el nombre del cliente.',
            'nombre.string' => 'Escribe el nombre del cliente.',
            'nombre.max' => 'El nombre puede tener hasta 120 caracteres.',
            'celular.required' => 'Escribe el celular del cliente.',
        ];
    }

    public function celular(): Celular
    {
        return Celular::desde($this->validated('celular'));
    }
}
