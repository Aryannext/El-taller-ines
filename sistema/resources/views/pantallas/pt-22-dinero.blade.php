{{-- PT-22 · Dinero: lo recibido en un período (HU-27, RN-33) y quién me debe (HU-26, RN-32) --}}
@extends('plantilla')

@section('titulo', 'Dinero')

@php
  $chipDeOrden = ['en-proceso' => 'chip-proceso', 'lista' => 'chip-lista', 'entregada' => 'chip-entregada'];
  $periodos = ['hoy' => 'Hoy', 'semana' => 'Semana', 'mes' => 'Mes', 'fechas' => 'Fechas'];
  $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  ['desde' => $desde, 'hasta' => $hasta] = $rango;
@endphp

@section('contenido')
  <header class="barra">
    <h1>Dinero</h1>
  </header>

  <main class="contenido">
    {{-- HU-27: cuánto recibí --}}
    <section class="seccion">
      <h2 class="titulo-seccion">Recibido</h2>
      <nav class="segmentos" aria-label="Período">
        @foreach ($periodos as $clave => $texto)
          <a href="{{ route('dinero', ['periodo' => $clave]) }}" @if ($periodo === $clave) aria-current="page" @endif>{{ $texto }}</a>
        @endforeach
      </nav>

      {{-- CA-27.3: un rango de fechas. Es un formulario GET: el resultado se puede volver a abrir con Atrás --}}
      @if ($periodo === 'fechas')
        <form class="pila" method="GET" action="{{ route('dinero') }}">
          <input type="hidden" name="periodo" value="fechas">
          <div class="dos-columnas">
            <div @class(['campo', 'con-error' => $erroresFechas?->has('desde')])>
              <label for="desde">Desde</label>
              <input class="entrada" type="date" id="desde" name="desde" value="{{ request('desde') }}" required>
            </div>
            <div @class(['campo', 'con-error' => $erroresFechas?->has('hasta')])>
              <label for="hasta">Hasta</label>
              <input class="entrada" type="date" id="hasta" name="hasta" value="{{ request('hasta') }}" required>
            </div>
          </div>
          @if ($erroresFechas && request()->hasAny(['desde', 'hasta']))
            <span class="mensaje-error" role="alert"><i class="i i-alerta"></i>{{ $erroresFechas->first() }}</span>
          @endif
          <button class="btn btn-secundario" type="submit">Ver lo recibido</button>
        </form>
      @endif

      @if ($recibido)
        <div class="tarjeta pila pila-junta" role="status">
          <span class="texto-2">
            @switch ($periodo)
              @case('hoy') Hoy, @fechaConDia($desde) @break
              @case('mes') {{ $meses[(int) $desde->format('n') - 1] }} de {{ $desde->format('Y') }} @break
              @default
                @if ($desde == $hasta) @fechaConDia($desde) @else Del @fecha($desde) al @fecha($hasta) @endif
            @endswitch
          </span>
          <span class="fuerte dinero cifra-recibido">{{ $recibido['recibido']->formato() }}</span>
          {{-- RN-31: el anulado no suma, y se dice para que la cuenta cuadre con lo que la dueña recuerda --}}
          <span class="texto-2 pequeno">{{ $recibido['pagos'] === 1 ? '1 pago' : $recibido['pagos'].' pagos' }}@if ($recibido['anulados'] > 0) · no incluye {{ $recibido['anulados'] === 1 ? '1 pago anulado' : $recibido['anulados'].' pagos anulados' }}@endif</span>
        </div>
      @endif
    </section>

    {{-- HU-26: quién me debe, de la mayor deuda a la menor --}}
    <section class="seccion">
      <h2 class="titulo-seccion">Quién me debe</h2>
      <div class="cifras">
        <div class="cifra principal">
          <span class="encabezado"><i class="i i-dinero"></i>Total por cobrar</span>
          <span class="valor dinero">{{ $total->formato() }}</span>
          <span class="nota">
            @if ($ordenes === [])
              Nadie te debe
            @else
              {{ count($ordenes) === 1 ? '1 orden' : count($ordenes).' órdenes' }} · de la mayor deuda a la menor
            @endif
          </span>
        </div>
      </div>

      @if ($ordenes !== [])
        <ul class="lista">
          @foreach ($ordenes as $fila)
            <li>
              <a class="fila" href="{{ route('ordenes.detalle', $fila['orden']) }}">
                <span class="principal">
                  <span class="nombre">{{ $fila['orden']->cliente->nombre }} · <span class="numero-orden">{{ \App\Dominio\Ordenes\NumeroDeOrden::desde($fila['orden']->numero)->formato() }}</span></span>
                  <span class="detalle"><span class="chip {{ $chipDeOrden[$fila['estado']->value] ?? 'chip-proceso' }}">{{ $fila['estado']->etiqueta() }}</span></span>
                </span>
                <span class="fuerte dinero">{{ $fila['saldo']->formato() }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif
    </section>
  </main>

  <x-navegacion actual="dinero" />
@endsection
