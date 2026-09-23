{{-- PT-04 · Registrar (HU-03) o corregir (HU-06) un cliente: el mismo formulario, con o sin $cliente --}}
@extends('plantilla')

@php
    $corrigiendo = $cliente !== null;
    $volver = $corrigiendo ? route('clientes.ficha', $cliente) : route('clientes.buscar');
@endphp

@section('titulo', $corrigiendo ? 'Corregir cliente' : 'Registrar cliente')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ $volver }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>{{ $corrigiendo ? 'Corregir cliente' : 'Registrar cliente' }}</h1>
  </header>

  <form class="formulario-pantalla" method="POST"
    action="{{ $corrigiendo ? route('clientes.corregir', $cliente) : route('clientes.guardar') }}">
    @csrf
    @if ($corrigiendo)
      @method('PUT')
    @endif
    @if ($desdeLaOrden ?? false)
      {{-- HU-10: se llegó desde una orden a medio llenar --}}
      <input type="hidden" name="desde" value="orden">
    @endif
    <main class="contenido">
      @if ($desdeLaOrden ?? false)
        <div class="banda banda-info" role="status"><i class="i i-check"></i><span>Lo que escribiste en la orden se conserva.</span></div>
      @endif

      <x-campo nombre="nombre" etiqueta="Nombre" :valor="$cliente?->nombre" autocomplete="name" maxlength="120" required />
      <x-campo nombre="celular" etiqueta="Celular" :valor="$cliente?->celular" inputmode="numeric" autocomplete="tel-national" required
        ayuda="A este número le llegan los avisos por WhatsApp." />
      <p class="texto-2 pequeno">Varios clientes pueden compartir el mismo celular, por ejemplo una madre y su hija. Solo se piden nombre y celular.</p>
    </main>

    <div class="acciones-fijas">
      <a class="btn btn-secundario" href="{{ $volver }}">Cancelar</a>
      <button class="btn btn-primario" type="submit">{{ $corrigiendo ? 'Guardar cambios' : 'Guardar cliente' }}</button>
    </div>
  </form>
@endsection
