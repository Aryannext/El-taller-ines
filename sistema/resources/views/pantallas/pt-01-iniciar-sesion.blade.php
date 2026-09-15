@extends('plantilla')

@section('titulo', 'Iniciar sesión')

@section('contenido')
  <main class="acceso">
    <div class="marca">
      <span class="sello"><i class="i i-tijeras"></i></span>
      <h1>{{ config('app.name') }}</h1>
      <p>Órdenes, entregas y cobros del taller, sin depender de la memoria.</p>
    </div>

    @if ($errors->any())
      <div class="banda banda-error" role="alert" id="error-acceso"><i class="i i-alerta"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <form class="pila pila-amplia" method="POST" action="{{ route('sesion.entrar') }}">
      @csrf
      <div class="campo">
        <label for="usuario">Usuario</label>
        <input class="entrada" id="usuario" name="usuario" autocomplete="username" value="{{ old('usuario') }}" required autofocus
          @if ($errors->any()) aria-invalid="true" aria-describedby="error-acceso" @endif>
      </div>
      <div class="campo">
        <label for="contrasena">Contraseña</label>
        <input class="entrada" id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primario btn-bloque" type="submit">Entrar</button>
    </form>

    <p class="texto-2 pequeno">Los datos del taller y de sus clientes se tratan según la Ley 1581 de 2012.</p>
  </main>
@endsection
