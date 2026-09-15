<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Aplicacion\Fotos\AgregarFoto;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Cliente;
use App\Modelos\Negocio;
use App\Modelos\Orden;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * HU-07 · Registrar una orden con sus prendas, en una sola operación. HU-17 · con las fotos que se tomaron al recibirlas.
 */
class RegistrarOrden
{
    public function __construct(
        private readonly Reloj $reloj,
        private readonly ResolverTipoDePrenda $resolverTipoDePrenda,
        private readonly AgregarFoto $agregarFoto,
    ) {}

    /**
     * @param  list<array{tipo_prenda_id: int|string, tipo_otro: ?string, descripcion_arreglo: string, precio: int, fotos?: list<string>}>  $prendas
     */
    public function ejecutar(Cliente $cliente, DateTimeImmutable $entrega, array $prendas, string $token): Orden
    {
        // RN-06: la orden y sus prendas se registran juntas
        if ($prendas === []) {
            throw new ReglaIncumplida('RN-06', 'Agrega al menos una prenda.', 'prendas');
        }

        // RNF-14: el mismo formulario enviado dos veces responde con la orden que ya se guardó
        $yaGuardada = Orden::where('token_formulario', $token)->first();
        if ($yaGuardada !== null) {
            return $yaGuardada;
        }

        /** @var array<int, list<array{ruta: string, ancho_px: int, alto_px: int, bytes: int}>> $archivos */
        $archivos = [];

        try {
            // Las fotos se reducen antes de abrir la transacción: tarda, y la transacción bloquea el negocio (RNF-03)
            foreach ($prendas as $indice => $prenda) {
                $archivos[$indice] = $this->agregarFoto->guardarArchivos($prenda['fotos'] ?? [], $cliente->negocio_id);
            }

            // RNF-13: si algo falla a mitad, no queda la orden, ni sus prendas, ni el tipo escrito con «Otro», ni sus fotos
            return DB::transaction(function () use ($cliente, $entrega, $prendas, $token, $archivos): Orden {
                // RN-08: bloquear el negocio hace que dos órdenes simultáneas no tomen el mismo número
                Negocio::whereKey($cliente->negocio_id)->lockForUpdate()->first();
                $ultimo = (int) Orden::withoutGlobalScopes()->where('negocio_id', $cliente->negocio_id)->max('numero');
                $numero = $ultimo === 0 ? NumeroDeOrden::desde(1) : NumeroDeOrden::desde($ultimo)->siguiente();

                $orden = new Orden([
                    'cliente_id' => $cliente->id,
                    'numero' => $numero->valor(),
                    'fecha_entrega_acordada' => $entrega->format('Y-m-d'),
                    // La hora de Colombia del reloj, no la que pondría Eloquent (RN-09)
                    'recibida_en' => $this->reloj->ahora(),
                    'token_formulario' => $token,
                ]);
                $orden->negocio_id = $cliente->negocio_id;
                $orden->save();

                foreach ($prendas as $indice => $prenda) {
                    $tipo = $this->resolverTipoDePrenda->resolver($cliente->negocio_id, $prenda['tipo_prenda_id'], $prenda['tipo_otro']);

                    $nuevaPrenda = $orden->prendas()->create([
                        'tipo_prenda_id' => $tipo->id,
                        'descripcion_arreglo' => $prenda['descripcion_arreglo'],
                        'precio' => Dinero::precio($prenda['precio'])->valor(),
                        'estado' => EstadoDePrenda::inicial(),
                    ]);

                    $this->agregarFoto->registrar($nuevaPrenda, $archivos[$indice] ?? []);
                }

                return $orden;
            });
        } catch (Throwable $error) {
            // La orden no quedó guardada con estas fotos: sus archivos no se quedan sueltos en el disco
            foreach ($archivos as $archivosDeLaPrenda) {
                $this->agregarFoto->descartar($archivosDeLaPrenda);
            }

            // Dos envíos llegaron al mismo tiempo: la clave única del token es la segunda barrera (RNF-14)
            if ($error instanceof UniqueConstraintViolationException) {
                return Orden::where('token_formulario', $token)->firstOr(fn () => throw $error);
            }

            throw $error;
        }
    }
}
