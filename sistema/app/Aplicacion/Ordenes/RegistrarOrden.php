<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

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

/**
 * HU-07 · Registrar una orden con sus prendas, en una sola operación.
 */
class RegistrarOrden
{
    public function __construct(
        private readonly Reloj $reloj,
        private readonly ResolverTipoDePrenda $resolverTipoDePrenda,
    ) {}

    /**
     * @param  list<array{tipo_prenda_id: int|string, tipo_otro: ?string, descripcion_arreglo: string, precio: int}>  $prendas
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

        try {
            // RNF-13: si algo falla a mitad, no queda la orden, ni sus prendas, ni el tipo escrito con «Otro»
            return DB::transaction(function () use ($cliente, $entrega, $prendas, $token): Orden {
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

                foreach ($prendas as $prenda) {
                    $tipo = $this->resolverTipoDePrenda->resolver($cliente->negocio_id, $prenda['tipo_prenda_id'], $prenda['tipo_otro']);

                    $orden->prendas()->create([
                        'tipo_prenda_id' => $tipo->id,
                        'descripcion_arreglo' => $prenda['descripcion_arreglo'],
                        'precio' => Dinero::precio($prenda['precio'])->valor(),
                        'estado' => EstadoDePrenda::inicial(),
                    ]);
                }

                return $orden;
            });
        } catch (UniqueConstraintViolationException $error) {
            // Dos envíos llegaron al mismo tiempo: la clave única del token es la segunda barrera (RNF-14)
            return Orden::where('token_formulario', $token)->firstOr(fn () => throw $error);
        }
    }
}
