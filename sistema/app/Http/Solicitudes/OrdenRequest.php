<?php

namespace App\Http\Solicitudes;

use App\Dominio\Compartido\Reloj;
use App\Modelos\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Orden con sus prendas (HU-07) y sus fotos (HU-17). Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class OrdenRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $prendas = $this->input('prendas');

        if (is_array($prendas)) {
            $this->merge(['prendas' => array_map(fn ($prenda) => is_array($prenda) ? PrendaRequest::normalizar($prenda) : $prenda, $prendas)]);
        }

        // HU-24: el abono se escribe como el precio, con signo y puntos de miles
        if (is_string($this->input('abono'))) {
            $abono = str_replace(['$', '.', ' '], '', $this->input('abono'));
            $this->merge(['abono' => $abono === '' ? null : $abono]);
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
            // RN-17 y RNF-03: hasta 3 fotos por prenda, de un tipo de imagen conocido
            'prendas.*.fotos' => ['nullable', 'array', 'max:3'],
            'prendas.*.fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            // HU-24: el abono al dejar la ropa es opcional; si se escribe, hay que decir cómo pagó (RN-25, RN-28)
            'abono' => ['nullable', 'integer', 'min:1'],
            'metodo_pago_id' => [
                'required_with:abono',
                Rule::exists('metodos_pago', 'id')->where('negocio_id', $this->user()?->negocio_id)->where('activo', true),
            ],
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
            ...FotoRequest::mensajes('prendas.*.'),
            'abono.integer' => 'Escribe el abono en pesos, sin centavos.',
            'abono.min' => 'El abono debe ser mayor que cero.',
            'metodo_pago_id.required_with' => 'Elige cómo pagó el abono.',
            'metodo_pago_id.exists' => 'Elige cómo pagó el abono.',
        ];
    }

    /**
     * El abono al dejar la ropa y cómo lo pagó, o null si no abonó nada (HU-24).
     *
     * @return array{0: int, 1: int}|null
     */
    public function abono(): ?array
    {
        $abono = $this->validated('abono');

        return $abono === null ? null : [(int) $abono, (int) $this->validated('metodo_pago_id')];
    }

    public function cliente(): Cliente
    {
        return Cliente::findOrFail((int) $this->validated('cliente_id'));
    }

    /**
     * @return list<array{tipo_prenda_id: int|string, tipo_otro: ?string, descripcion_arreglo: string, precio: int, fotos: list<string>}>
     */
    public function prendas(): array
    {
        return array_values(array_map(fn (array $prenda) => [
            'tipo_prenda_id' => $prenda['tipo_prenda_id'] === 'otro' ? 'otro' : (int) $prenda['tipo_prenda_id'],
            'tipo_otro' => $prenda['tipo_otro'] ?? null,
            'descripcion_arreglo' => $prenda['descripcion_arreglo'],
            'precio' => (int) $prenda['precio'],
            'fotos' => array_values(array_map(fn (UploadedFile $foto) => (string) $foto->getRealPath(), $prenda['fotos'] ?? [])),
        ], $this->validated('prendas')));
    }
}
