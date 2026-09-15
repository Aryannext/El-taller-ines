<?php

namespace Database\Seeders;

use App\Modelos\MetodoPago;
use App\Modelos\Negocio;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El negocio con el que se instala el sistema: la usuaria, los tipos de prenda iniciales y los métodos de pago.
 *
 *     php artisan db:seed --class=NegocioInicialSeeder
 */
class NegocioInicialSeeder extends Seeder
{
    /** RF-16: la lista con la que empieza cada negocio; crece con «Otro» (RN-43). */
    public const TIPOS_DE_PRENDA = ['Pantalón', 'Camisa', 'Blusa', 'Vestido', 'Falda', 'Chaqueta'];

    /** RN-25: el taller recibe pagos en efectivo y por Nequi. */
    public const METODOS_DE_PAGO = ['Efectivo', 'Nequi'];

    public function run(): void
    {
        $usuario = (string) config('instalacion.usuaria.usuario');
        $contrasena = (string) config('instalacion.usuaria.contrasena');

        if ($usuario === '' || $contrasena === '') {
            throw new RuntimeException('Define USUARIA_INICIAL_USUARIO y USUARIA_INICIAL_CONTRASENA en .env antes de instalar.');
        }

        DB::transaction(function () use ($usuario, $contrasena): void {
            $negocio = Negocio::create(['nombre' => 'Taller de costura']);

            $usuaria = new Usuario(['nombre' => 'Dueña del taller', 'usuario' => $usuario, 'contrasena' => $contrasena]);
            $usuaria->negocio_id = $negocio->id;
            $usuaria->save();

            foreach (self::TIPOS_DE_PRENDA as $nombre) {
                $tipo = new TipoPrenda(['nombre' => $nombre, 'activo' => true]);
                $tipo->negocio_id = $negocio->id;
                $tipo->save();
            }

            foreach (self::METODOS_DE_PAGO as $nombre) {
                $metodo = new MetodoPago(['nombre' => $nombre, 'activo' => true]);
                $metodo->negocio_id = $negocio->id;
                $metodo->save();
            }
        });
    }
}
