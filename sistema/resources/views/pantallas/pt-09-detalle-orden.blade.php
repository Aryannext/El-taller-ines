@extends('plantilla')

@section('titulo', 'Orden '.$numero)

@php
  $chipDeOrden = ['en-proceso' => 'chip-proceso', 'lista' => 'chip-lista', 'entregada' => 'chip-entregada', 'cancelada' => 'chip-cancelada'];
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
@endphp

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ Route::has('ordenes.listar') ? route('ordenes.listar') : route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    {{-- CA-08.3: el número de la bolsa va siempre arriba --}}
    <h1><span class="numero-orden numero-chico">{{ $numero }}</span><span class="sub"><a href="{{ route('clientes.ficha', $orden->cliente) }}">{{ $orden->cliente->nombre }}</a> · @celular($orden->cliente->celular)</span></h1>
  </header>

  <main class="contenido">
    <div class="tarjeta">
      <div class="entre">
        <span class="chip {{ $chipDeOrden[$estado->value] }}">{{ $estado->etiqueta() }}</span>
      </div>
      <dl class="montos">
        <dt>Recibida</dt><dd>@fechaConDia($orden->recibida_en)</dd>
        <dt>Entrega acordada</dt><dd class="fuerte">@fechaConDia($orden->fecha_entrega_acordada)</dd>
      </dl>
    </div>

    <section class="seccion">
      <h2 class="titulo-seccion">Prendas</h2>

      @foreach ($orden->prendas as $prenda)
        <div class="prenda">
          <div class="cabeza">
            <div class="pila pila-junta"><span class="tipo">{{ $prenda->tipoPrenda->nombre }}</span><span class="arreglo">{{ $prenda->descripcion_arreglo }}</span></div>
            <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
          </div>
          <div class="entre">
            <span></span>
            <span class="fuerte dinero">@dinero($prenda->precio)</span>
          </div>
        </div>
      @endforeach
    </section>

    {{-- Pagos, saldo, avisos y acciones llegan con HU-14, HU-31, HU-11, HU-16 y HU-17 --}}
    <section class="seccion">
      <h2 class="titulo-seccion">Dinero</h2>
      <div class="tarjeta">
        <dl class="montos">
          <dt>Valor de la orden</dt><dd class="fuerte">{{ $valor->formato() }}</dd>
        </dl>
      </div>
    </section>
  </main>

  <x-navegacion actual="ordenes" />
@endsection
