@extends('plantilla')

@section('titulo', 'Clientes')

@section('contenido')
  <header class="barra">
    <h1>Clientes</h1>
    <a class="boton-icono" href="{{ route('clientes.nuevo') }}" aria-label="Registrar cliente"><i class="i i-mas"></i></a>
  </header>

  <main class="contenido">
    <form class="campo" method="GET" action="{{ route('clientes.buscar') }}" role="search">
      <label class="sr" for="buscar">Buscar cliente</label>
      <div class="buscador"><i class="i i-buscar"></i><input class="entrada" id="buscar" name="q" type="search" value="{{ $busqueda }}" autocomplete="off" aria-describedby="ayuda-buscar"></div>
      <span class="ayuda" id="ayuda-buscar">Escribe parte del nombre o el celular. No importan las tildes.</span>
    </form>

    @php $total = $clientes->count(); @endphp

    @if ($total === 0 && $busqueda !== '')
      <p class="texto-2">No hay clientes con «{{ $busqueda }}».</p>
    @elseif ($total === 0)
      <p class="texto-2">Todavía no hay clientes registrados.</p>
    @else
      <p class="texto-2 pequeno">
        @if ($busqueda !== '')
          {{ $total }} {{ $total === 1 ? 'cliente encontrado' : 'clientes encontrados' }}
        @else
          {{ $total }} {{ $total === 1 ? 'cliente' : 'clientes' }}
        @endif
      </p>

      <ul class="lista">
        @foreach ($clientes as $cliente)
          <li>
            <a class="fila" href="{{ route('clientes.ficha', $cliente) }}">
              <span class="principal">
                <span class="nombre">{{ $cliente->nombre }}</span>
                <span class="detalle">@celular($cliente->celular)</span>
              </span>
              <i class="i i-derecha"></i>
            </a>
          </li>
        @endforeach
      </ul>
    @endif

    <div class="tarjeta entre">
      <span class="texto-2">¿No aparece?</span>
      <a class="btn btn-secundario btn-pequeno" href="{{ route('clientes.nuevo') }}"><i class="i i-mas i-sm"></i>Registrar cliente</a>
    </div>
  </main>

  <x-navegacion actual="clientes" />
@endsection
