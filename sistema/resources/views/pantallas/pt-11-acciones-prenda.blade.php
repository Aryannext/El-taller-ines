{{-- PT-11 · Acciones de una prenda (HU-20): una hoja sobre la orden, con en qué va y las demás acciones --}}
@extends('plantilla')

@section('titulo', $prenda->tipoPrenda->nombre.' · Orden '.$numero)

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
  $opciones = [
    'en_proceso' => ['i-reloj', $prenda->estado->value === 'terminada' ? 'Al medírsela le falta algo: vuelve al arreglo' : 'La estoy arreglando'],
    'terminada' => ['i-check', 'Si es la última, la orden queda lista para entregar'],
  ];
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

  <a class="velo" href="{{ route('ordenes.detalle', $orden) }}" aria-label="Cerrar" tabindex="-1"></a>
  <section class="hoja" role="dialog" aria-modal="true" aria-labelledby="titulo-hoja">
    <div class="entre">
      <div class="pila pila-junta">
        <h2 id="titulo-hoja">{{ $prenda->tipoPrenda->nombre }}</h2>
        <span class="texto-2">{{ $prenda->descripcion_arreglo }} · @dinero($prenda->precio)</span>
      </div>
      <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
    </div>

    @error('estado')
      <div class="banda banda-error" role="alert"><i class="i i-alerta"></i><span>{{ $message }}</span></div>
    @enderror

    {{-- CA-20.2: solo los estados permitidos desde el actual; nunca Entregada (RN-13, RN-20) --}}
    <form class="pila pila-nula" method="POST" action="{{ route('prendas.cambiar-estado', [$orden, $prenda]) }}" data-un-envio>
      @csrf
      <span class="titulo-seccion">¿En qué va?</span>
      <span class="opcion-hoja actual"><span class="i" aria-hidden="true"></span><span>{{ $prenda->estado->etiqueta() }}<span class="explicacion">Estado actual</span></span></span>
      @foreach ($estadosPosibles as $estado)
        <button class="opcion-hoja" type="submit" name="estado" value="{{ $estado->value }}"><i class="i {{ $opciones[$estado->value][0] }}"></i><span>{{ $estado->etiqueta() }}<span class="explicacion">{{ $opciones[$estado->value][1] }}</span></span></button>
      @endforeach
    </form>

    <div class="pila pila-nula">
      <span class="titulo-seccion">Otras acciones</span>
      <a class="opcion-hoja" href="{{ route('prendas.editar', [$orden, $prenda]) }}"><i class="i i-editar"></i><span>Corregir arreglo, precio o fotos</span></a>
      {{-- «Devolver sin arreglar» llega con HU-36 --}}
    </div>

    <p class="texto-2 pequeno">Las prendas terminadas se entregan con el botón Entregar de la orden.</p>
    <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">Cerrar</a>
  </section>
@endsection
