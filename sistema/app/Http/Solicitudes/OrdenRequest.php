<?php

namespace App\Http\Solicitudes;

use App\Dominio\Compartido\Reloj;
use App\Modelos\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Orden con sus prendas (HU-07). Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class OrdenRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $prendas = $this->input('prendas');

        if (is_array($prendas)) {
            $this->merge(['prendas' => array_map(fn ($prenda) => is_array($prenda) ? PrendaRequest::normalizar($prenda) : $prenda, $prendas)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hoy = app(Reloj::class)->hoy()->format('Y-m-d');

        return [
            'token_formulario' => ['required', 'uuid'],
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('negocio_id', $this->user()?->negocio_id)],
            // RN-07: la entrega puede ser hoy, no antes; «hoy» es el de Colombia (RN-09)
            'fecha_entrega_acordada' => ['required', 'date_format:Y-m-d', "after_or_equal:{$hoy}"],
            'prendas' => ['required', 'array', 'min:1'],
            ...PrendaRequest::reglas('prendas.*.'),
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
            'cliente_id.required' => 'Elige el cliente de la orden.',
            'cliente_id.integer' => 'Elige un cliente de la lista.',
            'cliente_id.exists' => 'Elige un cliente de la lista.',
            'fecha_entrega_acordada.required' => 'Elige la fecha de entrega acordada.',
            'fecha_entrega_acordada.date_format' => 'Elige la fecha de entrega acordada.',
            'fecha_entrega_acordada.after_or_equal' => 'La entrega no puede ser antes de la fecha de recepción.',
            'prendas.required' => 'Agrega al menos una prenda.',
            'prendas.array' => 'Agrega al menos una prenda.',
            'prendas.min' => 'Agrega al menos una prenda.',
            ...PrendaRequest::mensajes('prendas.*.'),
        ];
    }

    public function cliente(): Cliente
    {
        return Cliente::findOrFail((int) $this->validated('cliente_id'));
    }

    /**
     * @return list<array{tipo_prenda_id: int|string, tipo_otro: ?string, descripcion_arreglo: string, precio: int}>
     */
    public function prendas(): array
    {
        return array_values(array_map(fn (array $prenda) => [
            'tipo_prenda_id' => $prenda['tipo_prenda_id'] === 'otro' ? 'otro' : (int) $prenda['tipo_prenda_id'],
            'tipo_otro' => $prenda['tipo_otro'] ?? null,
            'descripcion_arreglo' => $prenda['descripcion_arreglo'],
            'precio' => (int) $prenda['precio'],
        ], $this->validated('prendas')));
    }
}
