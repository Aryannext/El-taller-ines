@extends('plantilla')

@section('titulo', 'Ajustes')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Ajustes</h1>
  </header>

  <main class="contenido">
    @if (session('exito'))
      <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>{{ session('exito') }}</span></div>
    @endif

    <section class="seccion">
      <h2 class="titulo-seccion">Contraseña</h2>
      <form class="tarjeta" method="POST" action="{{ route('ajustes.contrasena') }}">
        @csrf
        @method('PUT')
        <x-campo nombre="contrasena_actual" etiqueta="Contraseña actual" tipo="password" autocomplete="current-password" required />
        <x-campo nombre="contrasena_nueva" etiqueta="Nueva contraseña" tipo="password" ayuda="Al menos 8 caracteres." autocomplete="new-password" required />
        <x-campo nombre="contrasena_nueva_confirmation" etiqueta="Repite la nueva contraseña" tipo="password" autocomplete="new-password" required />
        <button class="btn btn-secundario" type="submit"><i class="i i-candado"></i>Cambiar contraseña</button>
      </form>
    </section>

    {{-- HU-35: el plazo tras el cual una orden lista se considera sin reclamar (RN-35) --}}
    <section class="seccion">
      <h2 class="titulo-seccion">Órdenes sin reclamar</h2>
      <form class="tarjeta pila" method="POST" action="{{ route('ajustes.plazo') }}">
        @csrf
        @method('PUT')
        <div @class(['campo', 'con-error' => $errors->has('dias_sin_reclamar')])>
          <label for="dias_sin_reclamar">Una orden lista queda sin reclamar después de</label>
          <div class="entre">
            <input class="entrada entrada-corta" id="dias_sin_reclamar" name="dias_sin_reclamar" inputmode="numeric"
              value="{{ old('dias_sin_reclamar', $plazo) }}" required
              @error('dias_sin_reclamar') aria-invalid="true" aria-describedby="error-dias_sin_reclamar" @enderror>
            <span class="fuerte crece">días</span>
          </div>
          @error('dias_sin_reclamar')
            {{-- CA-35.2 --}}
            <span class="mensaje-error" id="error-dias_sin_reclamar"><i class="i i-alerta"></i>{{ $message }}</span>
          @else
            <span class="ayuda">Entre 1 y 365 días.</span>
          @enderror
        </div>
        <button class="btn btn-secundario" type="submit">Guardar plazo</button>
      </form>
    </section>

    {{-- HU-16: la lista de tipos es propia del negocio y crece con lo que se escribe en «Otro» (RN-43) --}}
    <section class="seccion">
      <h2 class="titulo-seccion">Tipos de prenda</h2>
      @error('nombre')
        <div class="banda banda-error" role="alert"><i class="i i-alerta"></i><span>{{ $message }}</span></div>
      @enderror
      <ul class="lista">
        @foreach ($tipos as $tipo)
          <li class="fila alinear-arriba">
            <form class="pila pila-junta crece" method="POST" action="{{ route('ajustes.tipos.renombrar', $tipo) }}">
              @csrf
              @method('PUT')
              <label class="sr" for="tipo-{{ $tipo->id }}">Nombre del tipo de prenda</label>
              <input class="entrada" id="tipo-{{ $tipo->id }}" name="nombre" maxlength="60" required
                value="{{ $tipo->nombre }}" @class(['texto-2' => ! $tipo->activo])>
              @unless ($tipo->activo)
                <span class="detalle">No aparece al registrar prendas nuevas</span>
              @endunless
              <button class="btn btn-texto btn-pequeno alinear-inicio" type="submit">Guardar nombre</button>
            </form>
            <form method="POST" action="{{ route('ajustes.tipos.activo', $tipo) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="activo" value="{{ $tipo->activo ? '0' : '1' }}">
              <button class="btn btn-texto btn-pequeno" type="submit">{{ $tipo->activo ? 'Dejar de usar' : 'Volver a usar' }}</button>
            </form>
          </li>
        @endforeach
      </ul>
      <p class="texto-2 pequeno">Al dejar de usar un tipo, las prendas que ya lo tienen lo conservan; solo deja de ofrecerse al registrar.</p>
    </section>

    <form method="POST" action="{{ route('sesion.salir') }}">
      @csrf
      <button class="btn btn-peligro-borde" type="submit"><i class="i i-salir"></i>Cerrar sesión</button>
    </form>
  </main>
@endsection
