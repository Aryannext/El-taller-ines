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

    <form method="POST" action="{{ route('sesion.salir') }}">
      @csrf
      <button class="btn btn-peligro-borde" type="submit"><i class="i i-salir"></i>Cerrar sesión</button>
    </form>
  </main>
@endsection
