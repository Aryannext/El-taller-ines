@extends('plantilla')

@section('titulo', 'Órdenes')

@php
  $pestanas = [
    ['estado' => 'en-proceso', 'texto' => 'En proceso', 'contador' => $enProceso],
    ['estado' => 'lista', 'texto' => 'Listas', 'contador' => $listas],
    ['estado' => 'entregada', 'texto' => 'Entregadas', 'contador' => null],
    ['estado' => 'cancelada', 'texto' => 'Canceladas', 'contador' => null],
  ];
  $sinOrdenes = ['en-proceso' => 'en proceso', 'lista' => 'listas para entregar', 'entregada' => 'entregadas', 'cancelada' => 'canceladas'];
@endphp

@section('contenido')
  <header class="barra">
    <h1>Órdenes</h1>
  </header>

  <main class="contenido">
    {{-- CA-15.2: el número escrito en la bolsa abre la orden --}}
    <form class="campo" method="GET" action="{{ route('ordenes.listar') }}" role="search">
      <label class="sr" for="buscar">Buscar orden por número</label>
      <div class="buscador"><i class="i i-buscar"></i><input class="entrada" id="buscar" name="numero" type="search" inputmode="numeric" value="{{ $busqueda }}" placeholder="Número de la bolsa, por ejemplo 42" autocomplete="off"></div>
    </form>

    @if ($aviso)
      <p class="texto-2" role="status">{{ $aviso }}</p>
    @endif

    {{-- CA-15.1: filtrar por estado de avance (RN-18) --}}
    <nav class="pestanas" aria-label="Estado de avance">
      @foreach ($pestanas as $pestana)
        <a href="{{ route('ordenes.listar', ['estado' => $pestana['estado']]) }}" @if ($estado->value === $pestana['estado']) aria-current="page" @endif>
          {{ $pestana['texto'] }}
          @if ($pestana['contador'])
            <span class="contador neutro">{{ $pestana['contador'] }}</span>
          @endif
        </a>
      @endforeach
    </nav>

    @if ($ordenes->isEmpty())
      <p class="texto-2">No hay órdenes {{ $sinOrdenes[$estado->value] }}.</p>
    @else
      <ul class="lista">
        @foreach ($ordenes as $fila)
          @php
            $orden = $fila['orden'];
            $entregada = $estado->value === 'entregada' ? $orden->prendas->max('entregada_en') : null;
          @endphp
          <li>
            <a class="fila" href="{{ route('ordenes.detalle', $orden) }}">
              <span class="principal">
                <span class="nombre"><span class="numero-orden">{{ $fila['numero'] }}</span> · {{ $orden->cliente->nombre }}</span>
                <span class="detalle">
                  {{ $orden->prendas->map(fn ($prenda) => $prenda->tipoPrenda->nombre)->join(', ', ' y ') }}
                  @if ($estado->value === 'en-proceso')
                    · entrega @fecha($orden->fecha_entrega_acordada)
                  @elseif ($estado->value === 'lista' && $orden->lista_en)
                    · lista desde @fecha($orden->lista_en)
                  @elseif ($entregada)
                    · entregada @fecha($entregada)
                  @elseif ($orden->cancelada_en)
                    · cancelada @fecha($orden->cancelada_en)
                  @endif
                </span>
              </span>
              @if ($fila['estadoDePago'])
                <span class="lado">
                  <span @class(['chip', 'chip-pagada' => $fila['estadoDePago']->value === 'pagada', 'chip-cobrar' => $fila['estadoDePago']->value === 'por-cobrar'])>{{ $fila['estadoDePago']->etiqueta() }}</span>
                  @if ($fila['estadoDePago']->value === 'por-cobrar')
                    <span class="fuerte dinero">{{ $fila['saldo']->formato() }}</span>
                  @endif
                </span>
              @endif
            </a>
          </li>
        @endforeach
      </ul>

      @if ($ordenes->hasPages())
        <div class="entre">
          @if ($ordenes->previousPageUrl())
            <a class="btn btn-secundario" href="{{ $ordenes->previousPageUrl() }}">Anteriores</a>
          @else
            <span></span>
          @endif
          <span class="texto-2 pequeno">Página {{ $ordenes->currentPage() }} de {{ $ordenes->lastPage() }}</span>
          @if ($ordenes->nextPageUrl())
            <a class="btn btn-secundario" href="{{ $ordenes->nextPageUrl() }}">Siguientes</a>
          @else
            <span></span>
          @endif
        </div>
      @endif
    @endif
  </main>

  <x-navegacion actual="ordenes" />
@endsection
