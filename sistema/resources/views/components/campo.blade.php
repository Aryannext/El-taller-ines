{{-- Campo con su etiqueta, su mensaje de error junto a él (RNF-09) y su ayuda, como en los mockups --}}
@props(['nombre', 'etiqueta', 'tipo' => 'text', 'ayuda' => null])

@php
    $describe = trim(($errors->has($nombre) ? "error-{$nombre} " : '').($ayuda ? "ayuda-{$nombre}" : ''));
@endphp

<div @class(['campo', 'con-error' => $errors->has($nombre)])>
  <label for="{{ $nombre }}">{{ $etiqueta }}</label>
  <input class="entrada" id="{{ $nombre }}" name="{{ $nombre }}" type="{{ $tipo }}"
    @if ($tipo !== 'password') value="{{ old($nombre) }}" @endif
    @if ($errors->has($nombre)) aria-invalid="true" @endif
    @if ($describe !== '') aria-describedby="{{ $describe }}" @endif
    {{ $attributes }}>
  @error($nombre)
    <span class="mensaje-error" id="error-{{ $nombre }}"><i class="i i-alerta"></i>{{ $message }}</span>
  @enderror
  @if ($ayuda)
    <span class="ayuda" id="ayuda-{{ $nombre }}">{{ $ayuda }}</span>
  @endif
</div>
