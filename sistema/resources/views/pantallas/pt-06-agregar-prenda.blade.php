{{-- PT-06 con una sola prenda: la que se olvidó registrar al recibir la orden (HU-11). Usa el mismo bloque de prenda que la orden nueva --}}
@extends('plantilla')

@section('titulo', 'Agregar prenda')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('ordenes.detalle', $orden) }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Agregar prenda<span class="sub">{{ $numero }} · {{ $orden->cliente->nombre }}</span></h1>
  </header>

  <form class="formulario-pantalla" method="POST" action="{{ route('prendas.agregar', $orden) }}" enctype="multipart/form-data" data-un-envio>
    @csrf

    <main class="contenido">
      {{-- CA-11.1: lo que cambia en la orden al guardar --}}
      <div class="banda banda-info"><i class="i i-dinero"></i><span>
        La prenda queda Pendiente y el valor de la orden sube con su precio.
        @if ($estado === \App\Dominio\Ordenes\EstadoDeOrden::ListaParaEntregar)
          La orden está lista para entregar: volverá a En proceso.
        @endif
      </span></div>

      <div class="pila pila-amplia" data-prendas>
        @include('pantallas.partes.prenda', ['indice' => null, 'prenda' => old()])
      </div>
    </main>

    <div class="acciones-fijas">
      <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">Cancelar</a>
      <button class="btn btn-primario" type="submit"><i class="i i-check"></i>Guardar prenda</button>
    </div>
  </form>
@endsection
