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

    /** Margen bajo el máximo de 10 MB que acepta FotoRequest, para no depender de unos pocos kilobytes. */
    private const NUEVE_MEGAS = 9 * 1024 * 1024;

    /** @var list<int> */
    private const CALIDADES = [95, 90, 85, 80, 75, 70];

    /**
     * Una foto de celular pesada (CA-17.5): ruido de colores, que JPEG casi no logra comprimir.
     *
     * Cuánto pesa el archivo depende del codificador del sistema, no solo de la calidad: con el
     * mismo mosaico y calidad 90, la GD embebida de Windows da 5,8 MB y la libjpeg de Ubuntu pasa
     * de 10 MB, el máximo que acepta FotoRequest. Por eso se baja la calidad hasta caer dentro de
     * la ventana que la prueba necesita: más de 5 MB para que la foto sea de verdad pesada, y
     * menos del máximo para que la validación la acepte.
     */
    public static function pesada(): UploadedFile
    {
        $imagen = self::mosaicoDeRuido();
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'foto-pesada-'.bin2hex(random_bytes(6)).'.jpg';

        foreach (self::CALIDADES as $calidad) {
            imagejpeg($imagen, $ruta, $calidad);
            $bytes = filesize($ruta);

            if ($bytes > self::CINCO_MEGAS && $bytes <= self::NUEVE_MEGAS) {
                return new UploadedFile($ruta, 'vestido.jpg', 'image/jpeg', null, true);
            }
        }

        $ultimo = filesize($ruta);
        @unlink($ruta);

        throw new RuntimeException(
            'Ninguna calidad dio una foto entre 5 MB y 9 MB; la última pesó '.$ultimo.' bytes.'
        );
    }

    /**
     * El ruido va en un mosaico de 256 × 256 que se repite: sale igual en cada corrida (mt_srand)
     * y evita sortear los 6,75 millones de píxeles de la imagen completa.
     */
    private static function mosaicoDeRuido(): \GdImage
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

        return $imagen;
    }
}
