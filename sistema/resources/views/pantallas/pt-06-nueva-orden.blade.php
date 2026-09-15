@extends('plantilla')

@section('titulo', 'Nueva orden')

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    <h1>Nueva orden<span class="sub">Recibida hoy · @fechaConDia($hoy)</span></h1>
  </header>

  <form class="formulario-pantalla" method="POST" action="{{ route('ordenes.guardar') }}" enctype="multipart/form-data" data-un-envio>
    @csrf
    <input type="hidden" name="token_formulario" value="{{ old('token_formulario', $token) }}">

    <main class="contenido">
      @if ($errors->has('prendas') || $errors->has('token_formulario'))
        <div class="banda banda-error" role="alert"><i class="i i-alerta"></i><span>{{ $errors->first('prendas') ?: $errors->first('token_formulario') }}</span></div>
      @endif

      <section class="seccion">
        <h2 class="titulo-seccion">Cliente</h2>
        <div @class(['campo', 'con-error' => $errors->has('cliente_id')])>
          <label class="sr" for="cliente_id">Cliente</label>
          <select class="entrada" id="cliente_id" name="cliente_id" required
            @error('cliente_id') aria-invalid="true" aria-describedby="error-cliente_id" @enderror>
            <option value="">Elige el cliente</option>
            @foreach ($clientes as $cliente)
              <option value="{{ $cliente->id }}" @selected((int) old('cliente_id', $clienteElegido) === $cliente->id)>{{ $cliente->nombre }} · @celular($cliente->celular)</option>
            @endforeach
          </select>
          @error('cliente_id')
            <span class="mensaje-error" id="error-cliente_id"><i class="i i-alerta"></i>{{ $message }}</span>
          @enderror
        </div>
        <a class="btn btn-texto alinear-inicio" href="{{ route('clientes.nuevo') }}"><i class="i i-mas i-sm"></i>El cliente es nuevo</a>
      </section>

      <x-campo nombre="fecha_entrega_acordada" etiqueta="Fecha de entrega acordada" tipo="date"
        min="{{ $hoy->format('Y-m-d') }}" required ayuda="Puede ser hoy, pero no antes." />

      <section class="seccion">
        <h2 class="titulo-seccion">Prendas</h2>
        <div class="pila pila-amplia" data-prendas>
          @foreach (old('prendas', [[]]) as $indice => $prenda)
            @include('pantallas.partes.prenda', ['indice' => $indice, 'prenda' => is_array($prenda) ? $prenda : []])
          @endforeach
        </div>
        <button class="btn btn-secundario btn-bloque" type="button" data-agregar-prenda><i class="i i-mas"></i>Agregar otra prenda</button>
        <template id="plantilla-prenda">
          @include('pantallas.partes.prenda', ['indice' => '__INDICE__', 'prenda' => []])
        </template>
      </section>
    </main>

    <div class="acciones-fijas">
      <button class="btn btn-primario" type="submit"><i class="i i-check"></i>Guardar orden</button>
    </div>
  </form>
@endsection
