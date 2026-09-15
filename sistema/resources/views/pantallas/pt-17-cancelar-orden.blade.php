{{-- PT-17 · Cancelar la orden (HU-22): una hoja sobre la orden que explica qué implica antes de confirmar (RN-24, RNF-10) --}}
@extends('plantilla')

@section('titulo', 'Cancelar · Orden '.$numero)

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
  $nombre = strtok($orden->cliente->nombre, ' ') ?: $orden->cliente->nombre;
  $pagosValidos = $orden->pagos->whereNull('anulado_en');
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

  <a class="velo" href="{{ route('ordenes.detalle', $orden) }}" aria-label="No cancelar" tabindex="-1"></a>
  <section class="hoja" role="dialog" aria-modal="true" aria-labelledby="titulo-hoja">
    <h2 id="titulo-hoja">¿Cancelar la orden <span class="numero-orden">{{ $numero }}</span>?</h2>
    <p>Cancélala solo si {{ $nombre }} desistió del arreglo.</p>

    <ul class="pila lista-explicacion">
      <li>Deja de contar como trabajo pendiente y como deuda.</li>
      {{-- CA-22.1: los pagos no se borran --}}
      @if ($pagosValidos->count() === 1)
        <li>{{ $pagosValidos->first()->valor < $valor->valor() ? 'El abono' : 'El pago' }} de <strong class="dinero">@dinero($pagosValidos->first()->valor)</strong> sigue registrado.</li>
      @elseif ($pagosValidos->count() > 1)
        <li>Los pagos por <strong class="dinero">{{ $pagado->formato() }}</strong> siguen registrados.</li>
      @endif
      <li>No admite prendas, pagos ni cambios de estado.</li>
      <li><strong>No se puede reabrir.</strong> Su número no se vuelve a usar.</li>
    </ul>

    <form class="acciones" method="POST" action="{{ route('ordenes.cancelar', $orden) }}" data-un-envio>
      @csrf
      {{-- RNF-10: el servidor solo cancela si llega la confirmación de este botón --}}
      <button class="btn btn-peligro" type="submit" name="confirmacion" value="si">Sí, cancelar la orden</button>
      <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">No cancelar</a>
    </form>
  </section>
@endsection
