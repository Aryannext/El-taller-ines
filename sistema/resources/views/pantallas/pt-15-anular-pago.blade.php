{{-- PT-15 · ¿Anular este pago? (HU-25): una hoja sobre la orden. El pago no se borra (RN-31) --}}
@extends('plantilla')

@section('titulo', 'Anular pago · Orden '.$numero)

@php
  $describeMotivo = trim(($errors->has('motivo_anulacion') ? 'error-motivo ' : '').'ayuda-motivo');
@endphp

@section('contenido')
  {{-- La orden, atenuada detrás de la hoja --}}
  <div class="fondo-app" aria-hidden="true">
    <header class="barra">
      <span class="boton-icono"><i class="i i-atras"></i></span>
      <h1><span class="numero-orden numero-chico">{{ $numero }}</span><span class="sub">{{ $orden->cliente->nombre }} · @celular($orden->cliente->celular)</span></h1>
    </header>
    <main class="contenido">
      <div class="tarjeta">
        <dl class="montos">
          <dt>Valor de la orden</dt><dd>{{ $valor->formato() }}</dd>
          @if ($pagado->valor() > 0)
            <dt>Pagado</dt><dd>− {{ $pagado->formato() }}</dd>
          @endif
          <dt class="total">Saldo</dt><dd class="total">{{ $saldo->formato() }}</dd>
        </dl>
      </div>
    </main>
  </div>

  <a class="velo" href="{{ route('ordenes.detalle', $orden) }}" aria-label="No anular" tabindex="-1"></a>
  <section class="hoja" role="dialog" aria-modal="true" aria-labelledby="titulo-hoja">
    <h2 id="titulo-hoja">¿Anular este pago?</h2>

    <div class="tarjeta entre">
      <span><span class="fuerte">{{ $pago->valor < $valor->valor() ? 'Abono' : 'Pago' }} · {{ $pago->metodoPago->nombre }}</span><br><span class="texto-2 pequeno">@fechaConDia($pago->pagado_en) · @hora($pago->pagado_en)</span></span>
      <span class="fuerte dinero">@dinero($pago->valor)</span>
    </div>

    <p>El pago deja de contar en el saldo, pero <strong>no se borra</strong>: queda visible como anulado, con la fecha y el motivo.</p>

    <form class="pila pila-amplia" method="POST" action="{{ route('pagos.anular', [$orden, $pago]) }}" data-un-envio>
      @csrf
      <div @class(['campo', 'con-error' => $errors->has('motivo_anulacion')])>
        <label for="motivo_anulacion">Motivo</label>
        <input class="entrada" id="motivo_anulacion" name="motivo_anulacion" maxlength="255" required value="{{ old('motivo_anulacion') }}"
          aria-describedby="{{ $describeMotivo }}" @error('motivo_anulacion') aria-invalid="true" @enderror>
        @error('motivo_anulacion')
          {{-- CA-25.2 --}}
          <span class="mensaje-error" id="error-motivo"><i class="i i-alerta"></i>{{ $message }}</span>
        @enderror
        <span class="ayuda" id="ayuda-motivo">Es obligatorio.</span>
      </div>

      <dl class="montos">
        <dt>Saldo después de anular</dt><dd class="fuerte">{{ $saldoDespues->formato() }}</dd>
      </dl>

      {{-- RNF-10: el servidor solo anula si llega la confirmación de este botón --}}
      <div class="acciones">
        <button class="btn btn-peligro" type="submit" name="confirmacion" value="si">Anular pago</button>
        <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">No anular</a>
      </div>
    </form>
  </section>
@endsection
