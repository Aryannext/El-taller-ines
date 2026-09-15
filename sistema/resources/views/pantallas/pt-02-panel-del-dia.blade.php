@extends('plantilla')

@section('titulo', 'Hoy')

@section('contenido')
  <header class="barra">
    <h1>Hoy<span class="sub">{{ $negocio }}</span></h1>
    <a class="boton-icono" href="{{ route('ajustes') }}" aria-label="Ajustes"><i class="i i-ajustes"></i></a>
  </header>

  <main class="contenido">
    <div class="tarjeta pila">
      <span class="fuerte">Hola, {{ $usuaria }}</span>
      <span class="texto-2 pequeno">Aquí verás lo que necesita tu atención hoy: cobros, entregas atrasadas y avisos.</span>
    </div>
  </main>

  <x-navegacion actual="panel" />
@endsection
