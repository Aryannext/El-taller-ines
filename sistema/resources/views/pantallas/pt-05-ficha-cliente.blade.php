@extends('plantilla')

@section('titulo', $cliente->nombre)

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>{{ $cliente->nombre }}<span class="sub">@celular($cliente->celular)</span></h1>
  </header>

  <main class="contenido">
    {{-- Las órdenes y lo que debe llegan con HU-05; mientras no haya órdenes, este es su estado real (CA-05.3) --}}
    <p class="texto-2">{{ $cliente->nombre }} no tiene órdenes y no debe nada.</p>
  </main>
@endsection
