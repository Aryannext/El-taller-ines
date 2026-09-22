<?php

namespace App\Http\Solicitudes;

use App\Modelos\TipoPrenda;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Datos de una prenda (RN-10, RN-11, RN-43): la que se agrega a una orden que ya existe (HU-11) y la que se corrige (HU-12).
 * OrdenRequest usa las mismas reglas para cada prenda de la orden.
 * Mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class PrendaRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function reglas(string $prefijo = ''): array
    {
        return [
            "{$prefijo}tipo_prenda_id" => ['required', function (string $atributo, mixed $valor, Closure $fallar): void {
                if ($valor === 'otro') {
                    return;
                }
                $esDeLaLista = is_scalar($valor) && ctype_digit((string) $valor)
                    && TipoPrenda::whereKey((int) $valor)->where('activo', true)->exists();
                if (! $esDeLaLista) {
                    $fallar('Elige un tipo de prenda de la lista.');
                }
            }],
            "{$prefijo}tipo_otro" => ['nullable', "required_if:{$prefijo}tipo_prenda_id,otro", 'string', 'max:60'],
            "{$prefijo}descripcion_arreglo" => ['required', 'string', 'max:255'],
            "{$prefijo}precio" => ['required', 'integer', 'min:1', 'max:4294967295'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mensajes(string $prefijo = ''): array
    {
        return [
            "{$prefijo}tipo_prenda_id.required" => 'Elige el tipo de prenda.',
            "{$prefijo}tipo_otro.required_if" => 'Escribe qué tipo de prenda es.',
            "{$prefijo}tipo_otro.max" => 'El tipo de prenda puede tener hasta 60 caracteres.',
            "{$prefijo}descripcion_arreglo.required" => 'Escribe qué arreglo lleva la prenda.',
            "{$prefijo}descripcion_arreglo.max" => 'La descripción puede tener hasta 255 caracteres.',
            "{$prefijo}precio.required" => 'Escribe el precio del arreglo.',
            "{$prefijo}precio.integer" => 'Escribe el precio en pesos, sin centavos.',
            "{$prefijo}precio.min" => 'El precio debe ser mayor que cero.',
            "{$prefijo}precio.max" => 'Escribe el precio en pesos, sin centavos.',
        ];
    }

    /**
     * Quita el signo de pesos, los puntos de miles y los espacios: «$25.000» es 25000. «15.000,50» queda con coma y no es entero (RN-11).
     *
     * @param  array<string, mixed>  $prenda
     * @return array<string, mixed>
     */
    public static function normalizar(array $prenda): array
    {
        if (isset($prenda['precio']) && is_string($prenda['precio'])) {
            $prenda['precio'] = str_replace(['$', '.', ' '], '', $prenda['precio']);
        }

        return $prenda;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalizar($this->only('precio')));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        // Al corregir, una prenda no cambia de tipo (RF-12)
        if ($this->routeIs('prendas.corregir')) {
            return Arr::only(self::reglas(), ['descripcion_arreglo', 'precio']);
        }

        return [
            ...self::reglas(),
            // HU-17: la prenda que se agrega trae sus fotos, como en la orden nueva (RN-17, RNF-03)
            'fotos' => ['nullable', 'array', 'max:3'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...self::mensajes(), ...FotoRequest::mensajes()];
    }

    /**
     * @return list<string>
     */
    public function rutasDeFotos(): array
    {
        $fotos = $this->file('fotos', []);

        return array_values(array_map(fn (UploadedFile $foto) => (string) $foto->getRealPath(), is_array($fotos) ? $fotos : [$fotos]));
    }
}
