{{-- PT-13 · Corregir el arreglo o el precio de una prenda (HU-12). Las fotos (HU-17, HU-19) y eliminar la prenda (HU-13) llegan con sus historias --}}
@extends('plantilla')

@section('titulo', 'Corregir prenda')

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada'];
  $describePrecio = trim(($errors->has('precio') ? 'error-precio ' : '').($pagado->valor() > 0 ? 'ayuda-precio' : ''));
@endphp

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('ordenes.detalle', $orden) }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Corregir prenda<span class="sub">{{ $numero }} · {{ $orden->cliente->nombre }}</span></h1>
  </header>

  <form class="formulario-pantalla" method="POST" action="{{ route('prendas.corregir', [$orden, $prenda]) }}" data-un-envio>
    @csrf
    @method('PUT')

    <main class="contenido">
      {{-- RF-12: el tipo de prenda no se corrige --}}
      <div class="tarjeta entre">
        <span><span class="texto-2">Tipo de prenda</span><br><span class="fuerte">{{ $prenda->tipoPrenda->nombre }}</span></span>
        <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
      </div>

      <div @class(['campo', 'con-error' => $errors->has('descripcion_arreglo')])>
        <label for="descripcion_arreglo">Qué arreglo lleva</label>
        <textarea class="entrada" id="descripcion_arreglo" name="descripcion_arreglo" maxlength="255" required
          @error('descripcion_arreglo') aria-invalid="true" aria-describedby="error-descripcion_arreglo" @enderror>{{ old('descripcion_arreglo', $prenda->descripcion_arreglo) }}</textarea>
        @error('descripcion_arreglo')
          <span class="mensaje-error" id="error-descripcion_arreglo"><i class="i i-alerta"></i>{{ $message }}</span>
        @enderror
      </div>

      <div @class(['campo', 'con-error' => $errors->has('precio')])>
        <label for="precio">Precio</label>
        <div class="prefijo"><span>$</span><input class="entrada dinero" id="precio" name="precio" inputmode="numeric" required data-precio-a-corregir
          value="{{ old('precio', number_format($prenda->precio, 0, ',', '.')) }}"
          @if ($describePrecio !== '') aria-describedby="{{ $describePrecio }}" @endif
          @error('precio') aria-invalid="true" @enderror></div>
        @error('precio')
          <span class="mensaje-error" id="error-precio"><i class="i i-alerta"></i>{{ $message }}</span>
        @enderror
        @if ($pagado->valor() > 0)
          {{-- RN-16 --}}
          <span class="ayuda" id="ayuda-precio">La orden no puede valer menos de lo ya pagado ({{ $pagado->formato() }}).</span>
        @endif
      </div>

      {{-- Con JavaScript, cuenta cómo quedarían el valor y el saldo mientras se escribe el precio. La revisión que vale es la del servidor --}}
      <div class="banda banda-info" role="status" hidden data-valor-al-corregir
        data-valor="{{ $valor->valor() }}" data-precio-actual="{{ $prenda->precio }}" data-pagado="{{ $pagado->valor() }}">
        <i class="i i-dinero"></i><span data-texto></span>
      </div>
    </main>

    <div class="acciones-fijas">
      <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar cambios</button>
    </div>
  </form>
@endsection
