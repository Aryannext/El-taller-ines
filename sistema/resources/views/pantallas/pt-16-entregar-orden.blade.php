{{-- PT-16 · Entregar la orden (HU-21): una hoja sobre la orden con lo que sale, lo que se queda y el saldo (RN-20, RN-21) --}}
@extends('plantilla')

@section('titulo', 'Entregar · Orden '.$numero)

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
@endphp

@section('contenido')
  {{-- La orden, atenuada detrás de la hoja --}}
  <div class="fondo-app" aria-hidden="true">
    <header class="barra">
      <span class="boton-icono"><i class="i i-atras"></i></span>
      <h1><span class="numero-orden numero-chico">{{ $numero }}</span><span class="sub">{{ $orden->cliente->nombre }} · @celular($orden->cliente->celular)</span></h1>
    </header>
    <main class="contenido">
      @foreach ($orden->prendas as $prenda)
        <div class="prenda">
          <div class="cabeza">
            <div class="pila pila-junta"><span class="tipo">{{ $prenda->tipoPrenda->nombre }}</span><span class="arreglo">{{ $prenda->descripcion_arreglo }}</span></div>
            <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
          </div>
        </div>
      @endforeach
    </main>
  </div>

  <a class="velo" href="{{ route('ordenes.detalle', $orden) }}" aria-label="No entregar" tabindex="-1"></a>
  <section class="hoja" role="dialog" aria-modal="true" aria-labelledby="titulo-hoja">
    <h2 id="titulo-hoja">Entregar la <span class="numero-orden">{{ $numero }}</span></h2>

    <div class="pila pila-junta">
      <span class="titulo-seccion">Se entregan</span>
      @foreach ($seEntregan as $prenda)
        <div class="entre"><span>{{ $prenda->tipoPrenda->nombre }} · {{ mb_lcfirst($prenda->descripcion_arreglo) }}</span><span class="chip chip-terminada">Terminada</span></div>
      @endforeach
    </div>

    @if ($seQuedan !== [])
      {{-- CA-21.2 --}}
      <div class="pila pila-junta">
        <span class="titulo-seccion">Se queda en el taller</span>
        @foreach ($seQuedan as $prenda)
          <div class="entre"><span class="texto-2">{{ $prenda->tipoPrenda->nombre }} · {{ mb_lcfirst($prenda->descripcion_arreglo) }}</span><span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span></div>
        @endforeach
        <span class="texto-2 pequeno">Es una entrega parcial: la orden sigue En proceso.</span>
      </div>
    @endif

    @if ($aviso)
      {{-- CA-21.3: cuánto se debe, antes de confirmar --}}
      <div class="banda banda-aviso" role="alert"><i class="i i-alerta"></i><span class="fuerte">{{ $aviso }}</span></div>
    @endif

    <form class="acciones" method="POST" action="{{ route('ordenes.entregar', $orden) }}" data-un-envio>
      @csrf
      {{-- RN-21: con saldo, el servidor solo entrega si llega la confirmación de este botón --}}
      <button class="btn btn-primario" type="submit" name="confirmacion" value="si"><i class="i i-bolsa"></i>{{ $aviso ? 'Sí, entregar' : 'Entregar' }}</button>
      @if ($aviso)
        {{-- CU-24 2b: el cliente paga antes de llevársela --}}
        <a class="btn btn-secundario" href="{{ route('pagos.nuevo', $orden) }}"><i class="i i-dinero"></i>Registrar un pago primero</a>
      @endif
      <a class="btn btn-texto" href="{{ route('ordenes.detalle', $orden) }}">No entregar</a>
    </form>
  </section>
@endsection
