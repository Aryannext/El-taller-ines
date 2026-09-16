{{-- PT-18 · Avisos por enviar (HU-29): los que la dueña manda desde su WhatsApp, con el mensaje ya redactado (RN-40, RN-42) --}}
@extends('plantilla')

@section('titulo', 'Avisos por enviar')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Avisos por enviar<span class="sub">{{ count($pendientes) === 1 ? '1 cliente espera' : count($pendientes).' clientes esperan' }} saber que su ropa está lista</span></h1>
  </header>

  <main class="contenido">
    @if (session('exito'))
      <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>{{ session('exito') }}</span></div>
    @endif
    @error('aviso')
      <div class="banda banda-error" role="alert"><i class="i i-alerta"></i><span>{{ $message }}</span></div>
    @enderror

    @if ($pendientes === [])
      <div class="tarjeta pila">
        <span class="fuerte">No hay avisos por enviar.</span>
        <span class="texto-2 pequeno">Cuando una orden quede lista y el envío automático no salga, su aviso aparece aquí.</span>
      </div>
    @else
      <div class="banda banda-info"><i class="i i-mensaje"></i><span>El envío automático por WhatsApp no salió. Envía cada aviso con un toque desde tu WhatsApp.</span></div>

      @foreach ($pendientes as $pendiente)
        @php $aviso = $pendiente['aviso']; @endphp
        <article class="tarjeta">
          <div class="entre">
            <div class="pila pila-junta">
              <span class="fuerte">{{ $aviso->orden->cliente->nombre }}</span>
              <span class="texto-2 pequeno"><a class="enlace-pequeno" href="{{ route('ordenes.detalle', $aviso->orden) }}"><span class="numero-orden">{{ $pendiente['numero'] }}</span></a> · @celular($aviso->orden->cliente->celular) · lista @fecha($aviso->ciclo_lista_en), @hora($aviso->ciclo_lista_en)</span>
            </div>
            <span class="chip chip-asistido">Por enviar</span>
          </div>

          {{-- RN-42: el mensaje se arma con el saldo y las prendas de este momento --}}
          <div class="pila pila-junta">
            <span class="titulo-seccion">Mensaje</span>
            <p class="mensaje-aviso">{{ $pendiente['mensaje']->texto() }}</p>
          </div>

          <a class="btn btn-exito" href="{{ route('avisos.abrir-whatsapp', $aviso) }}"><i class="i i-enviar"></i>Abrir WhatsApp y enviar</a>

          <form class="entre confirmacion-aviso" method="POST" action="{{ route('avisos.confirmar-envio', $aviso) }}" data-un-envio>
            @csrf
            <span class="texto-2 pequeno">¿Ya lo enviaste?</span>
            <button class="btn btn-secundario btn-pequeno" type="submit"><i class="i i-check i-sm"></i>Sí, ya lo envié</button>
          </form>
        </article>
      @endforeach

      <p class="texto-2 pequeno">Si una orden deja de estar lista, por ejemplo porque una prenda vuelve a En proceso, su aviso sale de esta lista.</p>
    @endif
  </main>

  <x-navegacion actual="panel" />
@endsection
