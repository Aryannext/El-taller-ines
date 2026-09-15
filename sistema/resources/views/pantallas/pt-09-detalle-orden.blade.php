@extends('plantilla')

@section('titulo', 'Orden '.$numero)

@php
  $chipDeOrden = ['en-proceso' => 'chip-proceso', 'lista' => 'chip-lista', 'entregada' => 'chip-entregada', 'cancelada' => 'chip-cancelada'];
  $chipDePrenda = ['pendiente' => 'chip-pendiente', 'en_proceso' => 'chip-proceso', 'terminada' => 'chip-terminada', 'entregada' => 'chip-entregada', 'devuelta' => 'chip-devuelta'];
  $chipDePago = ['pagada' => 'chip-pagada', 'por-cobrar' => 'chip-cobrar'];
  // RN-41: el resultado de cada aviso, con lo que significa para la dueña
  $resultadoDeAviso = [
    'en_cola' => ['En cola', 'chip-pendiente', 'Se enviará en unos minutos.'],
    'enviado' => ['Enviado', 'chip-enviado', null],
    'pendiente_asistido' => ['Por enviar', 'chip-asistido', 'Falta enviarlo desde WhatsApp.'],
    'descartado' => ['Descartado', 'chip-descartado', 'No se envió: la orden dejó de estar lista antes de enviarlo.'],
  ];
  $canales = ['api_oficial' => 'API oficial', 'asistido' => 'Envío asistido'];
@endphp

@section('contenido')
  <header class="barra">
    <a class="boton-icono" href="{{ Route::has('ordenes.listar') ? route('ordenes.listar') : route('panel') }}" aria-label="Volver"><i class="i i-atras"></i></a>
    {{-- CA-08.3: el número de la bolsa va siempre arriba --}}
    <h1><span class="numero-orden numero-chico">{{ $numero }}</span><span class="sub"><a href="{{ route('clientes.ficha', $orden->cliente) }}">{{ $orden->cliente->nombre }}</a> · @celular($orden->cliente->celular)</span></h1>
  </header>

  <main class="contenido">
    @if (session('exito'))
      <div class="banda banda-exito" role="status"><i class="i i-check"></i><span>{{ session('exito') }}</span></div>
    @endif
    {{-- CA-12.3: por qué no se pudo corregir una prenda (RN-15, RN-24) --}}
    @error('prenda')
      <div class="banda banda-error" role="alert"><i class="i i-alerta"></i><span>{{ $message }}</span></div>
    @enderror

    <div class="tarjeta">
      <div class="entre">
        <span class="chip {{ $chipDeOrden[$estado->value] }}">{{ $estado->etiqueta() }}</span>
        @if ($estadoDePago)
          <span class="chip {{ $chipDePago[$estadoDePago->value] }}">{{ $estadoDePago->etiqueta() }}</span>
        @endif
      </div>
      <dl class="montos">
        <dt>Recibida</dt><dd>@fechaConDia($orden->recibida_en)</dd>
        <dt>Entrega acordada</dt><dd class="fuerte">@fechaConDia($orden->fecha_entrega_acordada)</dd>
        {{-- CA-14.2: RN-22 y RN-23 --}}
        @if ($orden->lista_en)
          <dt>Quedó lista</dt><dd>@fecha($orden->lista_en) · @hora($orden->lista_en)</dd>
        @endif
        @if ($entregadaEn)
          <dt>Entregada</dt><dd>@fecha($entregadaEn) · @hora($entregadaEn)</dd>
        @endif
      </dl>
    </div>

    <section class="seccion">
      {{-- HU-18 --}}
      <h2 class="titulo-seccion">Prendas <a href="{{ route('fotos.de-orden', $orden) }}">Ver fotos juntas</a></h2>

      @foreach ($orden->prendas as $prenda)
        <div @class(['prenda', 'atenuada' => $prenda->estado->value === 'devuelta'])>
          <div class="cabeza">
            <div class="pila pila-junta"><span class="tipo">{{ $prenda->tipoPrenda->nombre }}</span><span class="arreglo">{{ $prenda->descripcion_arreglo }}</span></div>
            <span class="chip {{ $chipDePrenda[$prenda->estado->value] }}">{{ $prenda->estado->etiqueta() }}</span>
          </div>
          <div class="entre">
            @if ($prenda->fotos->isNotEmpty())
              <div class="miniaturas">
                @foreach ($prenda->fotos as $foto)
                  <img class="foto" src="{{ route('fotos.mostrar', $foto) }}" alt="Foto {{ $foto->posicion }} de {{ $prenda->tipoPrenda->nombre }}" width="56" height="56" loading="lazy">
                @endforeach
              </div>
            @elseif (in_array($prenda->id, $prendasCorregibles, true))
              {{-- CA-17.4: sugiere tomarle una y lleva a donde se agrega --}}
              <a class="aviso-sugerencia enlace-sugerencia" href="{{ route('prendas.editar', [$orden, $prenda]) }}"><i class="i i-camara i-sm"></i>Sin foto · tomar una</a>
            @else
              <span class="aviso-sugerencia"><i class="i i-camara i-sm"></i>Sin foto</span>
            @endif
            <span class="fuerte dinero">@dinero($prenda->precio)</span>
          </div>
          {{-- HU-20: en qué va y las demás acciones de la prenda (PT-11). No se ofrece si ya no cambia (RN-15, RN-24) --}}
          @if (in_array($prenda->id, $prendasCorregibles, true))
            <a class="btn btn-secundario btn-pequeno" href="{{ route('prendas.acciones', [$orden, $prenda]) }}">Cambiar estado</a>
          @endif
        </div>
      @endforeach
    </section>

    <section class="seccion">
      <h2 class="titulo-seccion">Dinero</h2>
      <div class="tarjeta">
        <dl class="montos">
          <dt>Valor de la orden</dt><dd>{{ $valor->formato() }}</dd>
          @foreach ($orden->pagos as $pago)
            {{-- RN-31: un pago anulado sigue a la vista, tachado y con su motivo --}}
            <dt>{{ $pago->valor < $valor->valor() ? 'Abono' : 'Pago' }} · @fecha($pago->pagado_en) · {{ $pago->metodoPago->nombre }}
              @if ($pago->anulado_en)
                <br><span class="pequeno">Anulado el @fecha($pago->anulado_en): {{ $pago->motivo_anulacion }}</span>
              @endif
            </dt>
            <dd @class(['anulado' => $pago->anulado_en])>− @dinero($pago->valor)</dd>
          @endforeach
          <dt class="total">Saldo</dt><dd class="total">{{ $saldo->formato() }}</dd>
        </dl>
      </div>
    </section>

    @if ($orden->avisos->isNotEmpty())
      <section class="seccion">
        <h2 class="titulo-seccion">Avisos al cliente</h2>
        <ul class="lista">
          @foreach ($orden->avisos as $aviso)
            @php
              [$resultado, $chip, $explicacion] = $resultadoDeAviso[$aviso->estado];
              $momento = $aviso->resuelto_en ?? $aviso->generado_en;
            @endphp
            <li class="fila alinear-arriba">
              <span class="principal">
                <span class="nombre">@fecha($momento) · @hora($momento)</span>
                @if ($aviso->canal)
                  <span class="detalle">{{ $canales[$aviso->canal] }}</span>
                @endif
                <span class="detalle">{{ $aviso->mensaje ?? $explicacion }}</span>
              </span>
              <span class="chip {{ $chip }}">{{ $resultado }}</span>
            </li>
          @endforeach
        </ul>
      </section>
    @endif
  </main>

  <x-navegacion actual="ordenes" />
@endsection
