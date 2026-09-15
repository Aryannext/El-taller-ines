@extends('plantilla')

@section('titulo', 'Registrar cliente')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('clientes.buscar') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Registrar cliente</h1>
  </header>

  <form class="formulario-pantalla" method="POST" action="{{ route('clientes.guardar') }}">
    @csrf
    <main class="contenido">
      <x-campo nombre="nombre" etiqueta="Nombre" autocomplete="name" maxlength="120" required />
      <x-campo nombre="celular" etiqueta="Celular" inputmode="numeric" autocomplete="tel-national" required
        ayuda="A este número le llegan los avisos por WhatsApp." />
      <p class="texto-2 pequeno">Varios clientes pueden compartir el mismo celular, por ejemplo una madre y su hija. Solo se piden nombre y celular.</p>
    </main>

    <div class="acciones-fijas">
      <a class="btn btn-secundario" href="{{ route('clientes.buscar') }}">Cancelar</a>
      <button class="btn btn-primario" type="submit">Guardar cliente</button>
    </div>
  </form>
@endsection
