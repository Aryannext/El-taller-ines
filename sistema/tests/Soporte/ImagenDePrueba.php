<?php

namespace Tests\Soporte;

use GdImage;
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

    private const OBJETIVO = 7 * 1024 * 1024;

    /**
     * La calidad no se toca: a partir de 90, la GD de algunos sistemas deja de submuestrear el croma
     * y el archivo se dispara al triple, mientras que la de otros no. A 85 todas coinciden.
     */
    private const CALIDAD = 85;

    /** Techo de píxeles para no quedarse sin memoria buscando el peso. */
    private const ANCHO_MAXIMO = 4500;

    /**
     * Una foto de celular pesada (CA-17.5): ruido de colores, que JPEG casi no logra comprimir.
     *
     * El peso del archivo depende del codificador del sistema, así que no se fija de antemano: se
     * mide lo que salió y se ajusta el tamaño hasta caer en la ventana que la prueba necesita, más
     * de 5 MB para que la foto sea de verdad pesada y menos del máximo que acepta FotoRequest.
     * Como el ruido no se comprime, el peso sube casi en proporción a los píxeles y basta con un
     * par de vueltas.
     */
    public static function pesada(): UploadedFile
    {
        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'foto-pesada-'.bin2hex(random_bytes(6)).'.jpg';
        $ancho = 3000;
        $alto = 2250;
        $intentos = [];

        for ($vuelta = 0; $vuelta < 5; $vuelta++) {
            $imagen = self::mosaicoDeRuido($ancho, $alto);
            imagejpeg($imagen, $ruta, self::CALIDAD);
            imagedestroy($imagen);

            $bytes = filesize($ruta);
            $intentos[] = $ancho.'x'.$alto.' = '.$bytes.' bytes';

            if ($bytes > self::CINCO_MEGAS && $bytes <= self::NUEVE_MEGAS) {
                return new UploadedFile($ruta, 'vestido.jpg', 'image/jpeg', null, true);
            }

            $factor = min(sqrt(self::OBJETIVO / $bytes), 1.6);
            $ancho = min((int) round($ancho * $factor), self::ANCHO_MAXIMO);
            $alto = (int) round($ancho * 0.75);
        }

        @unlink($ruta);

        throw new RuntimeException(
            'No se logró una foto entre 5 MB y 9 MB en calidad '.self::CALIDAD.': '.implode('; ', $intentos)
        );
    }

    /**
     * El ruido va en un mosaico de 256 × 256 que se repite: sale igual en cada corrida (mt_srand)
     * y evita sortear los millones de píxeles de la imagen completa.
     */
    private static function mosaicoDeRuido(int $ancho, int $alto): GdImage
    {
        mt_srand(17);
        $mosaico = imagecreatetruecolor(256, 256);
        for ($x = 0; $x < 256; $x++) {
            for ($y = 0; $y < 256; $y++) {
                imagesetpixel($mosaico, $x, $y, mt_rand(0, 0xFFFFFF));
            }
        }

        $imagen = imagecreatetruecolor($ancho, $alto);
        for ($x = 0; $x < $ancho; $x += 256) {
            for ($y = 0; $y < $alto; $y += 256) {
                imagecopy($imagen, $mosaico, $x, $y, 0, 0, 256, 256);
            }
        }
        imagedestroy($mosaico);

        return $imagen;
    }
}
