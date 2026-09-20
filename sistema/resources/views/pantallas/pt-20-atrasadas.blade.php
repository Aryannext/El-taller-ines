{{-- PT-20 · HU-33 · CA-33.1, CA-33.2, CA-33.3 --}}
@extends('plantilla')

@section('titulo', 'Atrasadas')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Seguimiento<span class="sub">Órdenes atrasadas</span></h1>
  </header>

  <main class="contenido">
    <nav class="pestanas" aria-label="Seguimiento">
      <a href="{{ route('seguimiento.atrasadas') }}" aria-current="page">
        Atrasadas
        @if (count($ordenes))
          <span class="contador">{{ count($ordenes) }}</span>
        @endif
      </a>
      <a href="{{ route('seguimiento.sin-reclamar') }}">
        Sin reclamar
        @if ($sinReclamar)
          <span class="contador neutro">{{ $sinReclamar }}</span>
        @endif
      </a>
    </nav>

    @if ($ordenes === [])
      {{-- CA-32.3: nada atrasado es una buena noticia, no una lista vacía --}}
      <p class="texto-2">No hay órdenes atrasadas. Todo el trabajo en proceso está dentro de su fecha.</p>
    @else
      <p class="texto-2">Órdenes en proceso cuya fecha de entrega ya pasó, de la más atrasada a la menos.</p>

      <ul class="lista">
        @foreach ($ordenes as $fila)
          <li>
            <a class="fila" href="{{ route('ordenes.detalle', $fila['orden']) }}">
              <span class="insignia-dias rojo">{{ $fila['diasDeAtraso'] }}<small>{{ $fila['diasDeAtraso'] === 1 ? 'día' : 'días' }}</small></span>
              <span class="principal">
                <span class="nombre">{{ $fila['orden']->cliente->nombre }} · <span class="numero-orden">{{ $fila['numero'] }}</span></span>
                <span class="detalle">{{ $fila['prendas'] }} · entrega era el @fecha($fila['orden']->fecha_entrega_acordada)</span>
              </span>
              <i class="i i-derecha"></i>
            </a>
          </li>
        @endforeach
      </ul>
    @endif
  </main>

  <x-navegacion actual="panel" />
@endsection
