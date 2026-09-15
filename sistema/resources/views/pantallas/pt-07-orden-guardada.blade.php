@extends('plantilla')

@section('titulo', 'Orden guardada')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Cerrar"><i class="i i-cerrar"></i></a>
    <h1>Orden guardada</h1>
  </header>

  <main class="contenido">
    <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>La orden de {{ $orden->cliente->nombre }} quedó guardada.</span></div>

    <div class="pila centrado bolsa">
      <p class="fuerte titulo-bolsa">Escribe este número en la bolsa</p>
      <div class="etiqueta-bolsa"><small>Orden</small><span class="numero-orden">{{ $numero }}</span></div>
      <p class="texto-2">Así sabrás de quién son las prendas aunque estén en el mismo rincón.</p>
    </div>

    <div class="tarjeta">
      <dl class="montos">
        <dt>Cliente</dt><dd class="fuerte">{{ $orden->cliente->nombre }}</dd>
        <dt>Prendas</dt><dd>{{ $orden->prendas->map(fn ($prenda) => $prenda->tipoPrenda->nombre)->join(', ', ' y ') }}</dd>
        <dt>Entrega acordada</dt><dd>@fechaConDia($orden->fecha_entrega_acordada)</dd>
        <dt class="total">Valor</dt><dd class="total">{{ $valor->formato() }}</dd>
      </dl>
    </div>

    <div class="pila">
      @if (Route::has('ordenes.detalle'))
        <a class="btn btn-primario" href="{{ route('ordenes.detalle', $orden) }}">Ver la orden</a>
      @endif
      <a class="btn btn-secundario" href="{{ route('ordenes.nueva') }}">Registrar otra orden</a>
    </div>
  </main>
@endsection
