@extends('plantilla')

@section('titulo', $cliente->nombre)

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('clientes.buscar') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>{{ $cliente->nombre }}<span class="sub">@celular($cliente->celular)</span></h1>
    <a class="boton-icono" href="{{ route('clientes.editar', $cliente) }}" aria-label="Corregir datos"><i class="i i-editar"></i></a>
  </header>

  <main class="contenido">
    @if (session('exito'))
      <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>{{ session('exito') }}</span></div>
    @endif

    <a class="btn btn-primario" href="{{ route('ordenes.nueva', ['cliente' => $cliente->id]) }}"><i class="i i-mas"></i>Nueva orden para {{ $cliente->nombre }}</a>

    {{-- Las órdenes y lo que debe llegan con HU-05 --}}
    <p class="texto-2">{{ $cliente->nombre }} no tiene órdenes y no debe nada.</p>
  </main>

  <x-navegacion actual="clientes" />
@endsection
