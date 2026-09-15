{{-- PT-10 · Fotos de la orden (HU-18): para sacar del rincón las prendas correctas cuando el cliente viene a recogerlas --}}
@extends('plantilla')

@section('titulo', 'Fotos de la orden '.$numero)

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
  $cantidad = $orden->prendas->count();
@endphp

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('ordenes.detalle', $orden) }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Fotos de la <span class="numero-orden numero-chico">{{ $numero }}</span><span class="sub">{{ $orden->cliente->nombre }} · {{ $cantidad }} {{ $cantidad === 1 ? 'prenda' : 'prendas' }}</span></h1>
  </header>

  <main class="contenido">
    <div class="banda banda-info"><i class="i i-ampliar"></i><span>Toca una foto para verla grande y comparar con las prendas del rincón.</span></div>

    {{-- CA-18.1: agrupadas por prenda, con su tipo y su descripción --}}
    @foreach ($orden->prendas as $prenda)
      <section class="seccion">
        <div class="entre">
          <h2 class="titulo-prenda">{{ $prenda->tipoPrenda->nombre }} <span class="texto-2 peso-normal">· {{ $prenda->descripcion_arreglo }}</span></h2>
          <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
        </div>

        @if ($prenda->fotos->isNotEmpty())
          <div class="fotos fotos-grandes">
            @foreach ($prenda->fotos as $foto)
              {{-- CA-18.2: sin JavaScript, el enlace abre la foto completa; con JavaScript, se ve grande en el visor --}}
              <a class="foto-ampliable" href="{{ route('fotos.mostrar', $foto) }}" data-ampliar>
                <img class="foto" src="{{ route('fotos.mostrar', $foto) }}" alt="Foto {{ $foto->posicion }} de {{ $prenda->tipoPrenda->nombre }}: {{ $prenda->descripcion_arreglo }}" loading="lazy">
              </a>
            @endforeach
          </div>
        @else
          <div class="tarjeta entre">
            <span class="texto-2">Esta prenda no tiene fotos.</span>
            @if (in_array($prenda->id, $prendasCorregibles, true))
              <a class="btn btn-secundario btn-pequeno" href="{{ route('prendas.editar', [$orden, $prenda]) }}"><i class="i i-camara i-sm"></i>Tomar foto</a>
            @endif
          </div>
        @endif
      </section>
    @endforeach
  </main>

  <dialog class="visor" data-visor aria-label="Foto ampliada">
    <img alt="" data-visor-imagen>
    <form method="dialog"><button class="btn btn-secundario" type="submit">Cerrar</button></form>
  </dialog>

  <x-navegacion actual="ordenes" />
@endsection
