@extends('plantilla')

@section('titulo', 'Hoy')

@section('contenido')
  <header class="barra">
    <h1>Hoy<span class="sub">{{ $negocio }}</span></h1>
  </header>

  <main class="contenido">
    <div class="tarjeta pila">
      <span class="fuerte">Hola, {{ $usuaria }}</span>
      <span class="texto-2 pequeno">Aquí verás lo que necesita tu atención hoy: cobros, entregas atrasadas y avisos.</span>
    </div>

    {{-- Cerrar sesión se mueve a Ajustes (PT-23) cuando exista con HU-02 --}}
    <form method="POST" action="{{ route('sesion.salir') }}">
      @csrf
      <button class="btn btn-peligro-borde btn-bloque" type="submit"><i class="i i-salir"></i>Cerrar sesión</button>
    </form>
  </main>
@endsection
