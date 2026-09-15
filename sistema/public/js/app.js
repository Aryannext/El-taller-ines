// Comportamientos de la interfaz, sin librerías (docs/04-especificacion-tecnica/01-plataforma-y-dependencias.md).
// Todo lo que se hace aquí también lo garantiza el servidor: sin JavaScript el sistema sigue siendo correcto.

document.addEventListener('DOMContentLoaded', () => {
  prendasDeLaOrden();
  valorAlCorregir();
  unSoloEnvio();
});

// HU-07 y HU-09: agregar y quitar prendas, y pedir «¿Qué prenda es?» solo al elegir «Otro»
function prendasDeLaOrden() {
  const lista = document.querySelector('[data-prendas]');
  const plantilla = document.querySelector('#plantilla-prenda');
  if (!lista || !plantilla) {
    return;
  }

  let siguienteIndice = lista.querySelectorAll('[data-prenda]').length;

  const renumerar = () => {
    const prendas = lista.querySelectorAll('[data-prenda]');
    prendas.forEach((prenda, posicion) => {
      prenda.querySelector('[data-titulo]').textContent = `Prenda ${posicion + 1}`;
      const quitar = prenda.querySelector('[data-quitar]');
      quitar.setAttribute('aria-label', `Quitar prenda ${posicion + 1}`);
      quitar.hidden = prendas.length === 1;
    });
  };

  const alternarOtro = (tipo) => {
    const campoOtro = tipo.closest('[data-prenda]').querySelector('[data-otro]');
    const esOtro = tipo.value === 'otro';
    campoOtro.hidden = !esOtro;
    campoOtro.querySelector('input').required = esOtro;
  };

  lista.addEventListener('change', (evento) => {
    if (evento.target.matches('[data-tipo]')) {
      alternarOtro(evento.target);
    }
  });

  lista.addEventListener('click', (evento) => {
    const quitar = evento.target.closest('[data-quitar]');
    if (quitar) {
      quitar.closest('[data-prenda]').remove();
      renumerar();
    }
  });

  document.querySelector('[data-agregar-prenda]')?.addEventListener('click', () => {
    const contenedor = document.createElement('div');
    contenedor.innerHTML = plantilla.innerHTML.replaceAll('__INDICE__', String(siguienteIndice++));
    const prenda = contenedor.firstElementChild;
    lista.append(prenda);
    renumerar();
    prenda.querySelector('[data-tipo]').focus();
  });

  lista.querySelectorAll('[data-tipo]').forEach(alternarOtro);
  renumerar();
}

// HU-12: mientras se escribe el precio, cuenta cómo quedarían el valor y el saldo de la orden (PT-13)
function valorAlCorregir() {
  const aviso = document.querySelector('[data-valor-al-corregir]');
  const precio = document.querySelector('[data-precio-a-corregir]');
  if (!aviso || !precio) {
    return;
  }

  const valor = Number(aviso.dataset.valor);
  const precioActual = Number(aviso.dataset.precioActual);
  const pagado = Number(aviso.dataset.pagado);
  const pesos = (cantidad) => '$' + String(cantidad).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  const actualizar = () => {
    // Igual que el servidor: «$25.000» es 25000 (RN-11)
    const nuevoPrecio = Number(precio.value.replace(/[$.\s]/g, ''));
    if (!Number.isInteger(nuevoPrecio) || nuevoPrecio <= 0 || nuevoPrecio === precioActual) {
      aviso.hidden = true;
      return;
    }

    const nuevoValor = valor - precioActual + nuevoPrecio;
    aviso.querySelector('[data-texto]').textContent = nuevoValor < pagado
      ? `La orden quedaría valiendo ${pesos(nuevoValor)} y ya tiene ${pesos(pagado)} pagados. Primero anula el pago que sobra.`
      : `El valor de la orden pasará de ${pesos(valor)} a ${pesos(nuevoValor)} y el saldo a ${pesos(nuevoValor - pagado)}.`;
    aviso.hidden = false;
  };

  precio.addEventListener('input', actualizar);
  actualizar();
}

// RNF-14: deshabilita el botón al primer toque. La garantía real es el token único que revisa el servidor.
function unSoloEnvio() {
  document.querySelectorAll('form[data-un-envio]').forEach((formulario) => {
    formulario.addEventListener('submit', () => {
      formulario.querySelectorAll('button[type="submit"]').forEach((boton) => {
        boton.disabled = true;
      });
    });
  });
}
