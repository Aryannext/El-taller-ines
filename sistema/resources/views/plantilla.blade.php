<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('titulo') · {{ config('app.name') }}</title>
<link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
</head>
<body>
<div class="app">
@yield('contenido')
</div>
</body>
</html>
