{{-- PT-21 · HU-34 · CA-34.1, CA-34.2, CA-34.3 --}}
@extends('plantilla')

@section('titulo', 'Sin reclamar')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Seguimiento<span class="sub">Órdenes sin reclamar</span></h1>
  </header>

  <main class="contenido">
    <nav class="pestanas" aria-label="Seguimiento">
      <a href="{{ route('seguimiento.atrasadas') }}">
        Atrasadas
        @if ($atrasadas)
          <span class="contador">{{ $atrasadas }}</span>
        @endif
      </a>
      <a href="{{ route('seguimiento.sin-reclamar') }}" aria-current="page">
        Sin reclamar
        @if (count($ordenes))
          <span class="contador neutro">{{ count($ordenes) }}</span>
        @endif
      </a>
    </nav>

    @if ($ordenes === [])
      <p class="texto-2">No hay órdenes sin reclamar. Ninguna lleva más de {{ $plazo }} días esperando.</p>
    @else
      {{-- HU-35 hará configurable el plazo desde Ajustes; por ahora solo se informa cuál rige --}}
      <p class="texto-2">Órdenes listas hace más de {{ $plazo }} días que nadie ha recogido.</p>

      <ul class="lista">
        @foreach ($ordenes as $fila)
          <li>
            <a class="fila" href="{{ route('ordenes.detalle', $fila['orden']) }}">
              <span class="insignia-dias ambar">{{ $fila['diasDeEspera'] }}<small>{{ $fila['diasDeEspera'] === 1 ? 'día' : 'días' }}</small></span>
              <span class="principal">
                <span class="nombre">{{ $fila['orden']->cliente->nombre }} · <span class="numero-orden">{{ $fila['numero'] }}</span></span>
                <span class="detalle">{{ $fila['prendas'] === 1 ? '1 prenda' : $fila['prendas'].' prendas' }} sin reclamar · lista desde el @fecha($fila['orden']->lista_en)</span>
                <span class="detalle">
                  @celular($fila['orden']->cliente->celular)
                  @if ($fila['estadoDePago']->value === 'por-cobrar')
                    · debe <span class="dinero">{{ $fila['saldo']->formato() }}</span>
                  @endif
                </span>
              </span>
              <i class="i i-derecha"></i>
            </a>
          </li>
        @endforeach
      </ul>

      <div class="tarjeta">
        <p class="fuerte">En total: {{ count($ordenes) === 1 ? '1 orden' : count($ordenes).' órdenes' }} y {{ $prendas === 1 ? '1 prenda' : $prendas.' prendas' }} esperan en el taller.</p>
        <p class="texto-2 pequeno">Escríbele al cliente para acordar el día. Qué hacer con prendas que nunca se recogen es decisión del taller.</p>
      </div>
    @endif
  </main>

  <x-navegacion actual="panel" />
@endsection
