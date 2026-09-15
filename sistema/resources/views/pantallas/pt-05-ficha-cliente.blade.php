{{-- PT-05 · Ficha del cliente (HU-05): sus órdenes y cuánto debe, para responderle sin hacer cuentas de memoria --}}
@extends('plantilla')

@section('titulo', $cliente->nombre)

@php
  $chipDeOrden = ['en-proceso' => 'chip-proceso', 'lista' => 'chip-lista', 'entregada' => 'chip-entregada', 'cancelada' => 'chip-cancelada'];
  $chipDePago = ['pagada' => 'chip-pagada', 'por-cobrar' => 'chip-cobrar'];
  $cantidad = count($ordenes);
@endphp

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('clientes.buscar') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>{{ $cliente->nombre }}<span class="sub">@celular($cliente->celular)</span></h1>
    <a class="boton-icono" href="{{ route('clientes.editar', $cliente) }}" aria-label="Corregir datos"><i class="i i-editar"></i></a>
  </header>

  <main class="contenido">
    @if (session('exito'))
      <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>{{ session('exito') }}</span></div>
    @endif

    @if ($cantidad === 0)
      {{-- CA-05.3 --}}
      <p class="texto-2">{{ $cliente->nombre }} no tiene órdenes y no debe nada.</p>
    @else
      {{-- CA-05.1 y CA-05.2: la suma de los saldos, sin las órdenes canceladas (RN-27, RN-29, RN-32) --}}
      <div class="cifras">
        <div class="cifra principal">
          <span class="encabezado"><i class="i i-dinero"></i>Debe en total</span>
          <span class="valor dinero">{{ $debe->formato() }}</span>
          <span class="nota">
            @if ($ordenesQueDeben === [])
              No debe nada
            @else
              {{ count($ordenesQueDeben) === 1 ? 'Orden' : 'Órdenes' }} {{ collect($ordenesQueDeben)->join(', ', ' y ') }}
            @endif
          </span>
        </div>
      </div>
    @endif

    <a class="btn btn-primario" href="{{ route('ordenes.nueva', ['cliente' => $cliente->id]) }}"><i class="i i-mas"></i>Nueva orden para {{ $cliente->nombre }}</a>

    @if ($cantidad > 0)
      <section class="seccion">
        <h2 class="titulo-seccion">Órdenes <span class="contador neutro">{{ $cantidad }}</span></h2>
        <ul class="lista">
          @foreach ($ordenes as $fila)
            <li>
              <a class="fila" href="{{ route('ordenes.detalle', $fila['orden']) }}">
                <span class="principal">
                  <span class="nombre"><span class="numero-orden">{{ $fila['numero'] }}</span> · @fecha($fila['orden']->recibida_en)</span>
                  <span class="chips">
                    <span class="chip {{ $chipDeOrden[$fila['estado']->value] }}">{{ $fila['estado']->etiqueta() }}</span>
                    @if ($fila['estadoDePago'])
                      <span class="chip {{ $chipDePago[$fila['estadoDePago']->value] }}">{{ $fila['estadoDePago']->etiqueta() }}</span>
                    @endif
                  </span>
                </span>
                <span class="lado">
                  @if ($fila['estado']->value === 'cancelada')
                    <span class="texto-2 pequeno">No suma a la deuda</span>
                  @elseif ($fila['estadoDePago']?->value === 'por-cobrar')
                    <span class="fuerte dinero">{{ $fila['saldo']->formato() }}</span><span class="texto-2 pequeno">saldo</span>
                  @endif
                </span>
              </a>
            </li>
          @endforeach
        </ul>
      </section>
    @endif
  </main>

  <x-navegacion actual="clientes" />
@endsection
