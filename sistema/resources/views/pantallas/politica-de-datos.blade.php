{{-- Política de tratamiento de datos (RNF-26, Ley 1581 de 2012). Pública: se abre sin iniciar sesión --}}
@extends('plantilla')

@section('titulo', 'Política de tratamiento de datos')

@section('contenido')
  <header class="barra">
    @auth
      <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    @else
      <a class="boton-icono" href="{{ route('sesion.formulario') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    @endauth
    <h1>Política de tratamiento de datos<span class="sub">Ley 1581 de 2012</span></h1>
  </header>

  <main class="contenido">
    <section class="seccion">
      <h2 class="titulo-seccion">Qué datos se guardan</h2>
      <div class="tarjeta pila pila-junta">
        <p>Del cliente, solo <strong>su nombre y su número de celular</strong>. No se pide documento, dirección ni correo.</p>
        <p class="texto-2">También se guarda lo que el cliente deja en el taller: sus prendas, el arreglo que pidió, el precio, las fechas, los pagos y las fotos que se toman de las prendas para reconocerlas.</p>
      </div>
    </section>

    <section class="seccion">
      <h2 class="titulo-seccion">Para qué se usan</h2>
      <div class="tarjeta pila pila-junta">
        <p>Para <strong>llevar sus órdenes</strong> y para <strong>avisarle por WhatsApp cuando su ropa esté lista</strong>.</p>
        <p class="texto-2">No se usan para publicidad, no se venden y no se comparten con nadie más. El mensaje de aviso se envía desde el WhatsApp del taller al número que el cliente dio.</p>
      </div>
    </section>

    <section class="seccion">
      <h2 class="titulo-seccion">Quién responde por ellos</h2>
      <div class="tarjeta pila pila-junta">
        <p><strong>El taller</strong> es el responsable de los datos que guarda en este sistema.</p>
        <p class="texto-2">Solo la dueña entra con su usuario y su contraseña. Las fotos de las prendas no se pueden ver sin iniciar sesión.</p>
      </div>
    </section>

    <section class="seccion">
      <h2 class="titulo-seccion">Derechos del cliente</h2>
      <div class="tarjeta pila pila-junta">
        <p>Cualquier cliente puede pedirle al taller que le diga <strong>qué datos suyos tiene</strong>, que los <strong>corrija</strong> si están equivocados o que los <strong>elimine</strong> cuando ya no tenga trabajos pendientes.</p>
        <p class="texto-2">Para pedirlo, basta con decírselo a la dueña del taller, en persona o por el mismo número de WhatsApp por el que recibe los avisos.</p>
      </div>
    </section>

    <section class="seccion">
      <h2 class="titulo-seccion">Cuánto tiempo se conservan</h2>
      <div class="tarjeta pila pila-junta">
        <p>Mientras el cliente siga siendo cliente del taller, y mientras haga falta para responder por un trabajo hecho.</p>
        <p class="texto-2">El sistema guarda copias de respaldo de los últimos 14 días, y una copia semanal fuera del servidor, para no perder la información si algo falla.</p>
      </div>
    </section>
  </main>
@endsection
