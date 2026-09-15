<?php

namespace App\Http\Solicitudes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Fotos que se agregan a una prenda que ya existe (HU-17, PT-13). OrdenRequest usa los mismos mensajes para las fotos de cada prenda.
 * Reglas y mensajes de docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class FotoRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public static function mensajes(string $prefijo = ''): array
    {
        return [
            "{$prefijo}fotos.required" => 'Toma o elige una foto.',
            "{$prefijo}fotos.array" => 'Toma o elige una foto.',
            "{$prefijo}fotos.max" => 'Cada prenda puede tener hasta 3 fotos.',
            "{$prefijo}fotos.*.image" => 'La foto debe ser JPG, PNG o WebP.',
            "{$prefijo}fotos.*.mimes" => 'La foto debe ser JPG, PNG o WebP.',
            "{$prefijo}fotos.*.max" => 'La foto no puede pesar más de 10 MB.',
            // PHP rechaza el archivo antes de Laravel, por ejemplo si supera upload_max_filesize
            "{$prefijo}fotos.*.uploaded" => 'La foto no se pudo subir. Vuelve a intentarlo.',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // RN-17: la cantidad que todavía cabe la revisa AgregarFoto, con las fotos que la prenda ya tiene
            'fotos' => ['required', 'array', 'max:3'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::mensajes();
    }

    /**
     * @return list<string>
     */
    public function rutasTemporales(): array
    {
        // Después de validar, «fotos» es una lista de archivos (rules)
        $fotos = $this->file('fotos', []);

        return array_values(array_map(fn (UploadedFile $foto) => (string) $foto->getRealPath(), is_array($fotos) ? $fotos : [$fotos]));
    }
}
