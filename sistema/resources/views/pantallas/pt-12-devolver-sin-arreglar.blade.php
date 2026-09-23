{{-- PT-12 · Devolver una prenda sin arreglar (HU-36): una hoja sobre la orden que muestra en cuánto queda el valor antes de confirmar (RN-44, RNF-10) --}}
@extends('plantilla')

@section('titulo', 'Devolver sin arreglar · Orden '.$numero)

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
  $nombre = strtok($orden->cliente->nombre, ' ') ?: $orden->cliente->nombre;
  $saldoQueQueda = $valorSinLaPrenda->restar($pagado);
@endphp

@section('contenido')
  {{-- La orden, atenuada detrás de la hoja --}}
  <div class="fondo-app" aria-hidden="true">
    <header class="barra">
      <span class="boton-icono"><i class="i i-atras"></i></span>
      <h1><span class="numero-orden numero-chico">{{ $numero }}</span><span class="sub">{{ $orden->cliente->nombre }} · @celular($orden->cliente->celular)</span></h1>
    </header>
    <main class="contenido">
      @foreach ($orden->prendas as $otra)
        <div class="prenda">
          <div class="cabeza">
            <div class="pila pila-junta"><span class="tipo">{{ $otra->tipoPrenda->nombre }}</span><span class="arreglo">{{ $otra->descripcion_arreglo }}</span></div>
            <span class="chip {{ $chipDePrenda[$otra->estado->value] }}">{{ $otra->estado->etiqueta() }}</span>
          </div>
        </div>
      @endforeach
    </main>
  </div>

  <a class="velo" href="{{ route('ordenes.detalle', $orden) }}" aria-label="No devolver" tabindex="-1"></a>
  <section class="hoja" role="dialog" aria-modal="true" aria-labelledby="titulo-hoja">
    <h2 id="titulo-hoja">¿Devolver {{ mb_strtolower($prenda->tipoPrenda->nombre) }} sin arreglar?</h2>
    <p>{{ $nombre }} se lleva {{ mb_strtolower($prenda->tipoPrenda->nombre) }} sin el arreglo «{{ $prenda->descripcion_arreglo }}». Queda como <strong>Devuelta</strong> y ese arreglo ya no se cobra.</p>

    {{-- RN-26: el valor baja porque el precio de la prenda devuelta deja de contar --}}
    <dl class="montos tarjeta">
      <dt>Valor de la orden</dt>
      <dd><span class="texto-2 tachado">{{ $valor->formato() }}</span> → {{ $valorSinLaPrenda->formato() }}</dd>
      @if ($pagado->esMayorQue(\App\Dominio\Pagos\Dinero::pesos(0)))
        <dt>Pagado</dt><dd>− {{ $pagado->formato() }}</dd>
      @endif
      <dt class="total">Saldo</dt><dd class="total">{{ $saldoQueQueda->formato() }}</dd>
    </dl>

    <p class="texto-2 pequeno">No se puede deshacer. Si todas las prendas quedaran devueltas, lo que corresponde es cancelar la orden.</p>

    <form class="acciones" method="POST" action="{{ route('prendas.devolver', [$orden, $prenda]) }}" data-un-envio>
      @csrf
      {{-- RNF-10: el servidor solo devuelve si llega la confirmación de este botón --}}
      <button class="btn btn-primario" type="submit" name="confirmacion" value="si"><i class="i i-devolver"></i>Sí, devolver sin arreglar</button>
      <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">No, dejarla en la orden</a>
    </form>
  </section>
@endsection
