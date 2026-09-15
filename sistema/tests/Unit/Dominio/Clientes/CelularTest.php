<?php

namespace Tests\Unit\Dominio\Clientes;

use App\Dominio\Clientes\Celular;
use App\Dominio\Compartido\ReglaIncumplida;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RN-03 · El teléfono debe poder recibir WhatsApp. Sin Laravel ni base de datos.
 */
class CelularTest extends TestCase
{
    public function test_rn_03_el_telefono_debe_poder_recibir_whatsapp(): void
    {
        $celular = Celular::desde('3104567890');

        $this->assertSame('3104567890', $celular->valor());
        $this->assertSame('573104567890', $celular->enFormatoInternacional());
        $this->assertSame('3104567890', Celular::desde('310 456 7890')->valor(), 'Los espacios no cuentan');
    }

    #[DataProvider('noSonCelularesColombianos')]
    public function test_rn_03_rechaza_lo_que_no_es_un_celular_colombiano(string $texto): void
    {
        try {
            Celular::desde($texto);
            $this->fail("Se aceptó «{$texto}»");
        } catch (ReglaIncumplida $error) {
            $this->assertSame('RN-03', $error->regla);
            $this->assertSame('celular', $error->campo);
            $this->assertSame('Escribe un celular colombiano de 10 dígitos que empiece por 3', $error->mensajeParaUsuaria);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function noSonCelularesColombianos(): array
    {
        return [
            'fijo' => ['6014567890'],
            '9 dígitos' => ['310456789'],
            '11 dígitos' => ['31045678901'],
            'con letras' => ['310456789a'],
            'vacío' => [''],
        ];
    }
}
