<?php

declare(strict_types=1);

namespace App\Infraestructura\Fotos;

use App\Dominio\Fotos\AlmacenDeFotos;
use Illuminate\Contracts\Filesystem\Factory as Discos;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Guarda las fotos en el disco privado, reducidas y sin metadatos, con Intervention Image y GD.
 * Pasos de docs/04-especificacion-tecnica/05-avisos-fotos-y-reloj.md (RNF-03, RNF-25, RNF-26).
 */
final class AlmacenLocalPrivado implements AlmacenDeFotos
{
    public const PESO_MAXIMO = 400 * 1024;

    // 1.600 px y luego 1.200 px, como pide la especificación. Los siguientes son un resguardo:
    // la columna bytes no admite más de 400 KB, así que la foto sigue reduciéndose hasta caber.
    private const LADOS = [1600, 1200, 900, 600, 400];

    private const CALIDAD_INICIAL = 80;

    private const CALIDAD_MINIMA = 50;

    private const DISCO = 'privado';

    public function __construct(private readonly Discos $discos) {}

    public function guardar(string $rutaTemporal, string $carpeta): array
    {
        // Enderezar: se gira según cómo estaba el celular. Quitar metadatos: sin EXIF ni ubicación GPS (RNF-26)
        $imagen = ImageManager::gd(autoOrientation: true, strip: true)->read($rutaTemporal);
        $jpeg = $this->reducir($imagen);

        $ruta = trim($carpeta, '/').'/'.Str::uuid().'.jpg';
        $this->disco()->put($ruta, $jpeg->toString());

        return ['ruta' => $ruta, 'ancho_px' => $imagen->width(), 'alto_px' => $imagen->height(), 'bytes' => $jpeg->size()];
    }

    public function entregar(string $ruta): string
    {
        return $this->disco()->path($ruta);
    }

    public function eliminar(string $ruta): void
    {
        $this->disco()->delete($ruta);
    }

    /**
     * Sin agrandar las fotos pequeñas: baja la calidad de 10 en 10 hasta 50 y, si aún pesa más de 400 KB, reduce el lado mayor.
     */
    private function reducir(ImageInterface $imagen): EncodedImageInterface
    {
        foreach (self::LADOS as $lado) {
            $imagen->scaleDown($lado, $lado);

            for ($calidad = self::CALIDAD_INICIAL; $calidad >= self::CALIDAD_MINIMA; $calidad -= 10) {
                $jpeg = $imagen->toJpeg(quality: $calidad);
                if ($jpeg->size() <= self::PESO_MAXIMO) {
                    return $jpeg;
                }
            }
        }

        return $imagen->toJpeg(quality: self::CALIDAD_MINIMA);
    }

    private function disco(): Filesystem
    {
        return $this->discos->disk(self::DISCO);
    }
}
