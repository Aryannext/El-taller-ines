@extends('plantilla')

@section('titulo', 'Hoy')

@section('contenido')
  <header class="barra">
    <h1>Hoy<span class="sub">{{ $negocio }}</span></h1>
    <a class="boton-icono" href="{{ route('ajustes') }}" aria-label="Ajustes"><i class="i i-ajustes"></i></a>
  </header>

  <main class="contenido">
    <div class="tarjeta pila">
      <span class="fuerte">Hola, {{ $usuaria }}</span>
      <span class="texto-2 pequeno">Aquí verás lo que necesita tu atención hoy: cobros, entregas atrasadas y avisos.</span>
    </div>

    {{-- HU-29: el envío automático no salió y estos avisos esperan un toque --}}
    @if ($avisosPorEnviar > 0)
      <a class="banda banda-info" href="{{ route('avisos.pendientes') }}">
        <i class="i i-mensaje"></i>
        <span>{{ $avisosPorEnviar === 1 ? 'Hay 1 aviso por enviar' : "Hay {$avisosPorEnviar} avisos por enviar" }} desde tu WhatsApp.</span>
      </a>
    @endif
  </main>

  <x-navegacion actual="panel" />
@endsection
