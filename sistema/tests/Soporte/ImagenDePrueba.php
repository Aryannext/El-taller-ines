<?php

namespace Tests\Soporte;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Imágenes reales para las pruebas de fotos: Storage::fake guarda archivos, pero la reducción necesita una imagen de verdad (RNF-03).
 */
final class ImagenDePrueba
{
    private const CINCO_MEGAS = 5 * 1024 * 1024;

    /**
     * Una foto de celular de más de 5 MB (CA-17.5): ruido de colores, que JPEG casi no logra comprimir.
     * Con 3.000 × 2.250 px y calidad 90 pesa unos 5,8 MB, por debajo del máximo de 10 MB que se acepta.
     */
    public static function pesada(): UploadedFile
    {
        mt_srand(17);
        $mosaico = imagecreatetruecolor(256, 256);
        for ($x = 0; $x < 256; $x++) {
            for ($y = 0; $y < 256; $y++) {
                imagesetpixel($mosaico, $x, $y, mt_rand(0, 0xFFFFFF));
            }
        }

        $imagen = imagecreatetruecolor(3000, 2250);
        for ($x = 0; $x < 3000; $x += 256) {
            for ($y = 0; $y < 2250; $y += 256) {
                imagecopy($imagen, $mosaico, $x, $y, 0, 0, 256, 256);
            }
        }

        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'foto-pesada-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($imagen, $ruta, 90);

        if (filesize($ruta) <= self::CINCO_MEGAS) {
            throw new RuntimeException('La imagen de prueba debía pesar más de 5 MB y pesa '.filesize($ruta).' bytes.');
        }

        return new UploadedFile($ruta, 'vestido.jpg', 'image/jpeg', null, true);
    }
}
