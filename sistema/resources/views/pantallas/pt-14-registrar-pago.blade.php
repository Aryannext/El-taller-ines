{{-- PT-14 · Registrar un pago o abono (HU-23): una hoja sobre la orden, con el saldo de ese momento --}}
@extends('plantilla')

@section('titulo', 'Registrar pago · Orden '.$numero)

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

  <a class="velo" href="{{ route('ordenes.detalle', $orden) }}" aria-label="Cancelar" tabindex="-1"></a>
  <section class="hoja" role="dialog" aria-modal="true" aria-labelledby="titulo-hoja">
    <div class="pila pila-junta">
      <h2 id="titulo-hoja">Registrar pago</h2>
      <span class="texto-2"><span class="numero-orden">{{ $numero }}</span> · {{ $orden->cliente->nombre }} · saldo <strong class="dinero">{{ $saldo->formato() }}</strong></span>
    </div>

    <form class="pila pila-amplia" method="POST" action="{{ route('pagos.guardar', $orden) }}" data-un-envio>
      @csrf
      <input type="hidden" name="token_formulario" value="{{ old('token_formulario', $token) }}">
      @error('token_formulario')
        <div class="banda banda-error" role="alert"><i class="i i-alerta"></i><span>{{ $message }}</span></div>
      @enderror

      <div @class(['campo', 'con-error' => $errors->has('valor')])>
        <label for="valor">Valor</label>
        <div class="prefijo"><span>$</span><input class="entrada dinero" id="valor" name="valor" inputmode="numeric" required data-valor-del-pago
          value="{{ old('valor') }}"
          @error('valor') aria-invalid="true" aria-describedby="error-valor" @enderror></div>
        @error('valor')
          {{-- CA-23.3: RN-28 --}}
          <span class="mensaje-error" id="error-valor"><i class="i i-alerta"></i>{{ $message }}</span>
        @enderror
        {{-- Con JavaScript, escribe el saldo en el valor --}}
        <button class="btn btn-texto btn-pequeno alinear-inicio" type="button" data-usar-saldo="{{ number_format($saldo->valor(), 0, ',', '.') }}" hidden>Usar el saldo completo: {{ $saldo->formato() }}</button>
      </div>

      {{-- RN-25: efectivo o Nequi, los métodos activos del negocio --}}
      <fieldset @class(['campo', 'con-error' => $errors->has('metodo_pago_id')])>
        <legend class="etiqueta">Método</legend>
        <div class="segmentos">
          @foreach ($metodos as $metodo)
            <label><input type="radio" name="metodo_pago_id" value="{{ $metodo->id }}" required @checked((string) old('metodo_pago_id') === (string) $metodo->id)>{{ $metodo->nombre }}</label>
          @endforeach
        </div>
        @error('metodo_pago_id')
          <span class="mensaje-error"><i class="i i-alerta"></i>{{ $message }}</span>
        @enderror
      </fieldset>

      <p class="texto-2 pequeno">La fecha del pago es hoy, {{ mb_strtolower(\App\Providers\AppServiceProvider::fecha($hoy, true)) }}.</p>

      <div class="acciones">
        <button class="btn btn-primario" type="submit">Guardar pago</button>
        <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">Cancelar</a>
      </div>
    </form>
  </section>
@endsection
