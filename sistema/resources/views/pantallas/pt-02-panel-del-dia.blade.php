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

    {{-- CA-32.1: las cuatro cifras del día. Aparecen siempre, en cero cuando no hay nada pendiente (CA-32.3) --}}
    <div class="cifras">
      {{-- PT-22 la construye HU-26; mientras no exista, la cifra informa sin llevar a ninguna parte --}}
      @if (Route::has('dinero'))
        <a class="cifra principal" href="{{ route('dinero') }}">
      @else
        <div class="cifra principal">
      @endif
        <span class="encabezado"><i class="i i-dinero"></i>Por cobrar</span>
        <span class="valor dinero">{{ $porCobrar->formato() }}</span>
        <span class="nota">Saldo de las órdenes no canceladas</span>
      @if (Route::has('dinero'))
        </a>
      @else
        </div>
      @endif

      {{-- CA-32.2: tocar la cifra abre su lista --}}
      <a class="cifra alerta" href="{{ route('seguimiento.atrasadas') }}">
        <span class="encabezado"><i class="i i-reloj"></i>Atrasadas</span>
        <span class="valor">{{ $atrasadas }}</span>
        <span class="nota">Pasó la fecha de entrega</span>
      </a>

      <a class="cifra atencion" href="{{ route('seguimiento.sin-reclamar') }}">
        <span class="encabezado"><i class="i i-bolsa"></i>Sin reclamar</span>
        <span class="valor">{{ $ordenesSinReclamar }}</span>
        <span class="nota">{{ $prendasSinReclamar === 1 ? '1 prenda' : "{$prendasSinReclamar} prendas" }} · más de {{ $plazoSinReclamar }} días</span>
      </a>

      <a class="cifra" href="{{ route('avisos.pendientes') }}">
        <span class="encabezado"><i class="i i-mensaje"></i>Avisos por enviar</span>
        <span class="valor">{{ $avisosPorEnviar }}</span>
        <span class="nota">Enviar con un toque</span>
      </a>
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
