{{-- Barra de navegación principal de los mockups. Un destino cuya ruta todavía no existe se muestra deshabilitado. --}}
@props(['actual' => null])

@php
    $destinos = [
        'panel' => ['ruta' => 'panel', 'icono' => 'i-inicio', 'texto' => 'Hoy'],
        'ordenes' => ['ruta' => 'ordenes.listar', 'icono' => 'i-ordenes', 'texto' => 'Órdenes'],
        'nueva' => ['ruta' => 'ordenes.nueva', 'icono' => '', 'texto' => 'Nueva'],
        'clientes' => ['ruta' => 'clientes.buscar', 'icono' => 'i-clientes', 'texto' => 'Clientes'],
        'dinero' => ['ruta' => 'dinero', 'icono' => 'i-dinero', 'texto' => 'Dinero'],
    ];
@endphp

<nav class="navegacion" aria-label="Principal">
  @foreach ($destinos as $clave => $destino)
    @if (Route::has($destino['ruta']))
      <a href="{{ route($destino['ruta']) }}" @class(['nueva' => $clave === 'nueva']) @if ($clave === $actual) aria-current="page" @endif><i class="i {{ $destino['icono'] }}"></i>{{ $destino['texto'] }}</a>
    @else
      <span @class(['pendiente', 'nueva' => $clave === 'nueva']) aria-disabled="true"><i class="i {{ $destino['icono'] }}"></i>{{ $destino['texto'] }}</span>
    @endif
  @endforeach
</nav>
