<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titulo') · {{ config('app.name') }}</title>
<meta name="theme-color" content="#2a44a8">
<link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
{{-- ADR-006: el celular puede instalar el sistema como app (HT-07) --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('iconos/icono-192.png') }}" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('iconos/icono-192.png') }}">
<script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<div class="app">
@yield('contenido')
</div>
</body>
</html>
