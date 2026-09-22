{{-- Una prenda de la orden: tipo, «Otro», arreglo, precio y fotos (RN-10, RN-11, RN-17, RN-43). En la plantilla, $indice es __INDICE__.
     Con $indice en null es la única prenda del formulario, la que se agrega a una orden que ya existe (HU-11), y sus campos van sin prefijo --}}
@php
    $nombre = fn (string $campo) => $indice === null ? $campo : "prendas[{$indice}][{$campo}]";
    $id = fn (string $campo) => $indice === null ? "prenda-{$campo}" : "prenda-{$indice}-{$campo}";
    $clave = fn (string $campo) => $indice === null ? $campo : "prendas.{$indice}.{$campo}";
    $error = fn (string $campo) => $errors->first($clave($campo));
    $esOtro = ($prenda['tipo_prenda_id'] ?? null) === 'otro';
    $errorFotos = $error('fotos') ?: $errors->first($clave('fotos.*'));
@endphp

<div class="prenda" data-prenda>
  @if ($indice !== null)
    <div class="entre">
      <span class="tipo" data-titulo>Prenda</span>
      <button class="boton-icono" type="button" aria-label="Quitar prenda" data-quitar><i class="i i-basura"></i></button>
    </div>
  @endif

  <div @class(['campo', 'con-error' => $error('tipo_prenda_id')])>
    <label for="{{ $id('tipo_prenda_id') }}">Tipo de prenda</label>
    <select class="entrada" id="{{ $id('tipo_prenda_id') }}" name="{{ $nombre('tipo_prenda_id') }}" required data-tipo
      @if ($error('tipo_prenda_id')) aria-invalid="true" aria-describedby="{{ $id('tipo_prenda_id') }}-error" @endif>
      <option value="">Elige el tipo</option>
      @foreach ($tipos as $tipo)
        <option value="{{ $tipo->id }}" @selected((string) ($prenda['tipo_prenda_id'] ?? '') === (string) $tipo->id)>{{ $tipo->nombre }}</option>
      @endforeach
      <option value="otro" @selected($esOtro)>Otro…</option>
    </select>
    @if ($error('tipo_prenda_id'))
      <span class="mensaje-error" id="{{ $id('tipo_prenda_id') }}-error"><i class="i i-alerta"></i>{{ $error('tipo_prenda_id') }}</span>
    @endif
  </div>

  <div @class(['campo', 'con-error' => $error('tipo_otro')]) data-otro @unless ($esOtro) hidden @endunless>
    <label for="{{ $id('tipo_otro') }}">¿Qué prenda es?</label>
    <input class="entrada" id="{{ $id('tipo_otro') }}" name="{{ $nombre('tipo_otro') }}" value="{{ $prenda['tipo_otro'] ?? '' }}" maxlength="60"
      @if ($esOtro) required @endif
      aria-describedby="{{ $error('tipo_otro') ? $id('tipo_otro').'-error ' : '' }}{{ $id('tipo_otro') }}-ayuda"
      @if ($error('tipo_otro')) aria-invalid="true" @endif>
    @if ($error('tipo_otro'))
      <span class="mensaje-error" id="{{ $id('tipo_otro') }}-error"><i class="i i-alerta"></i>{{ $error('tipo_otro') }}</span>
    @endif
    <span class="ayuda" id="{{ $id('tipo_otro') }}-ayuda">Quedará en la lista para las próximas prendas.</span>
  </div>

  <div @class(['campo', 'con-error' => $error('descripcion_arreglo')])>
    <label for="{{ $id('descripcion_arreglo') }}">Qué arreglo lleva</label>
    <textarea class="entrada" id="{{ $id('descripcion_arreglo') }}" name="{{ $nombre('descripcion_arreglo') }}" maxlength="255" required
      @if ($error('descripcion_arreglo')) aria-invalid="true" aria-describedby="{{ $id('descripcion_arreglo') }}-error" @endif>{{ $prenda['descripcion_arreglo'] ?? '' }}</textarea>
    @if ($error('descripcion_arreglo'))
      <span class="mensaje-error" id="{{ $id('descripcion_arreglo') }}-error"><i class="i i-alerta"></i>{{ $error('descripcion_arreglo') }}</span>
    @endif
  </div>

  <div @class(['campo', 'con-error' => $error('precio')])>
    <label for="{{ $id('precio') }}">Precio</label>
    <div class="prefijo"><span>$</span><input class="entrada dinero" id="{{ $id('precio') }}" name="{{ $nombre('precio') }}" value="{{ $prenda['precio'] ?? '' }}" inputmode="numeric" required
      @if ($error('precio')) aria-invalid="true" aria-describedby="{{ $id('precio') }}-error" @endif></div>
    @if ($error('precio'))
      <span class="mensaje-error" id="{{ $id('precio') }}-error"><i class="i i-alerta"></i>{{ $error('precio') }}</span>
    @endif
  </div>

  {{-- HU-17: hasta 3 fotos, con la cámara trasera o desde la galería (RN-17). El servidor las reduce (RNF-03) --}}
  <div @class(['campo', 'con-error' => $errorFotos]) data-fotos>
    <span class="etiqueta">Fotos <span class="texto-2 pequeno peso-normal">· hasta 3</span></span>
    <div class="dos-columnas">
      <label class="btn btn-secundario"><i class="i i-camara"></i>Tomar foto<input class="sr" type="file" name="{{ $nombre('fotos') }}[]" accept="image/*" capture="environment" data-foto></label>
      <label class="btn btn-secundario"><i class="i i-galeria"></i>Galería<input class="sr" type="file" name="{{ $nombre('fotos') }}[]" accept="image/*" multiple data-foto></label>
    </div>
    <span class="texto-2 pequeno" role="status" data-fotos-elegidas hidden></span>
    @if ($errorFotos)
      <span class="mensaje-error"><i class="i i-alerta"></i>{{ $errorFotos }}</span>
    @endif
    {{-- Un archivo no se puede devolver al formulario: si la orden no se guardó, las fotos se eligen de nuevo --}}
    @if ($errors->any())
      <span class="ayuda">Vuelve a elegir las fotos que ya habías tomado.</span>
    @endif
    {{-- CA-17.4 --}}
    <span class="aviso-sugerencia" data-sin-foto><i class="i i-alerta i-sm"></i>Sin foto: tómale una para reconocerla después.</span>
  </div>
</div>
