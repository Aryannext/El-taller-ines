{{-- PT-13 · Corregir el arreglo o el precio (HU-12) y agregar fotos (HU-17). Eliminar una foto (HU-19) o la prenda (HU-13) llegan con sus historias --}}
@extends('plantilla')

@section('titulo', 'Corregir prenda')

@php
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada'];
  $describePrecio = trim(($errors->has('precio') ? 'error-precio ' : '').($pagado->valor() > 0 ? 'ayuda-precio' : ''));
  // Las fotos ya vienen cargadas con la orden (DetalleDeOrden)
  $fotos = $orden->prendas->firstWhere('id', $prenda->id)?->fotos ?? collect();
  $errorFotos = $errors->first('fotos') ?: $errors->first('fotos.*');
@endphp

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('ordenes.detalle', $orden) }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Corregir prenda<span class="sub">{{ $numero }} · {{ $orden->cliente->nombre }}</span></h1>
  </header>

  <div class="formulario-pantalla">
    <main class="contenido">
      @if (session('exito'))
        <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>{{ session('exito') }}</span></div>
      @endif

      {{-- RF-12: el tipo de prenda no se corrige --}}
      <div class="tarjeta entre">
        <span><span class="texto-2">Tipo de prenda</span><br><span class="fuerte">{{ $prenda->tipoPrenda->nombre }}</span></span>
        <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
      </div>

      <form class="pila pila-amplia" id="corregir-prenda" method="POST" action="{{ route('prendas.corregir', [$orden, $prenda]) }}" data-un-envio>
        @csrf
        @method('PUT')

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
      </form>

      {{-- HU-17: fotos para reconocer la prenda en el rincón (RN-17) --}}
      <section @class(['campo', 'con-error' => $errorFotos]) data-fotos>
        <span class="etiqueta">Fotos <span class="texto-2 pequeno peso-normal">· {{ $fotos->count() }} de 3</span></span>

        @if ($fotos->isNotEmpty())
          <div class="fotos">
            @foreach ($fotos as $foto)
              <img class="foto" src="{{ route('fotos.mostrar', $foto) }}" alt="Foto {{ $foto->posicion }} de {{ $prenda->tipoPrenda->nombre }}" loading="lazy">
            @endforeach
          </div>
        @endif

        @if ($fotos->count() < 3)
          <form class="pila" method="POST" action="{{ route('fotos.agregar', [$orden, $prenda]) }}" enctype="multipart/form-data" data-un-envio data-subir-fotos>
            @csrf
            <div class="dos-columnas">
              <label class="btn btn-secundario"><i class="i i-camara"></i>Tomar foto<input class="sr" type="file" name="fotos[]" accept="image/*" capture="environment" data-foto></label>
              <label class="btn btn-secundario"><i class="i i-galeria"></i>Galería<input class="sr" type="file" name="fotos[]" accept="image/*" multiple data-foto></label>
            </div>
            <span class="texto-2 pequeno" role="status" data-fotos-elegidas hidden></span>
            {{-- Con JavaScript, las fotos se suben apenas se eligen --}}
            <button class="btn btn-secundario" type="submit" data-sin-js>Guardar fotos</button>
          </form>
        @else
          <span class="ayuda">Ya tiene las 3 fotos que caben.</span>
        @endif

        @if ($errorFotos)
          <span class="mensaje-error"><i class="i i-alerta"></i>{{ $errorFotos }}</span>
        @endif
        @if ($fotos->isEmpty())
          {{-- CA-17.4 --}}
          <span class="aviso-sugerencia"><i class="i i-alerta i-sm"></i>Sin foto: tómale una para reconocerla después.</span>
        @endif
      </section>
    </main>

    <div class="acciones-fijas">
      <a class="btn btn-secundario" href="{{ route('ordenes.detalle', $orden) }}">Cancelar</a>
      <button class="btn btn-primario" type="submit" form="corregir-prenda">Guardar cambios</button>
    </div>
  </div>
@endsection
