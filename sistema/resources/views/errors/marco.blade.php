{{-- Marco de las páginas de error. El mensaje dice qué pasó y qué hacer, en palabras del taller y sin
     un solo detalle técnico (RNF-09, PM-07 A05). La forma viene del componente «not-found-01» de 21st.dev,
     traducida al diseño propio: aquí no hay React ni hojas de estilo de afuera (RNF-23, política de contenido). --}}
@extends('plantilla')

@section('titulo', $titulo)

@section('contenido')
  <main class="error">
    <p class="error-codigo">Error {{ $codigo }}</p>
    <h1 class="error-titulo">{{ $titulo }}</h1>
    <p class="error-texto">{{ $texto }}</p>

    <div class="error-acciones">
      @foreach ($acciones as $accion)
        <a class="btn {{ $accion['clase'] }}" href="{{ $accion['ruta'] }}">{{ $accion['texto'] }}</a>
      @endforeach
    </div>

    @if ($sugerencias ?? false)
      <div class="error-sugerencias">
        <p class="etiqueta">O vaya derecho a</p>
        <ul>
          @foreach ([
            ['ordenes.listar', 'Órdenes', 'Todas las órdenes, con su buscador'],
            ['clientes.buscar', 'Clientes', 'Buscar un cliente por nombre o celular'],
            ['dinero', 'Dinero', 'Quién debe y cuánto ha entrado'],
          ] as [$ruta, $destino, $queHay])
            <li>
              <a href="{{ route($ruta) }}">
                <span><span class="destino">{{ $destino }}</span><span class="que-hay">{{ $queHay }}</span></span>
                <i class="i i-derecha" aria-hidden="true"></i>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    @endif
  </main>
@endsection
