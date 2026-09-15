// Comportamientos de la interfaz, sin librerías (docs/04-especificacion-tecnica/01-plataforma-y-dependencias.md).
// Todo lo que se hace aquí también lo garantiza el servidor: sin JavaScript el sistema sigue siendo correcto.

document.addEventListener('DOMContentLoaded', () => {
  prendasDeLaOrden();
  fotosDeLasPrendas();
  visorDeFotos();
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

// HU-17: reduce las fotos en el navegador para gastar menos datos, cuenta las elegidas y, en PT-13, las sube al elegirlas.
// El servidor las vuelve a reducir y revisa que no pasen de 3 (RNF-03, RN-17).
function fotosDeLasPrendas() {
  document.querySelectorAll('[data-sin-js]').forEach((boton) => {
    boton.hidden = true;
  });

  // Escucha en el documento: las prendas que se agregan con «Agregar otra prenda» también tienen fotos
  document.addEventListener('change', async (evento) => {
    const entrada = evento.target;
    if (!(entrada instanceof HTMLInputElement) || !entrada.matches('[data-foto]')) {
      return;
    }

    await reducirFotos(entrada);

    const campo = entrada.closest('[data-fotos]');
    const total = [...campo.querySelectorAll('[data-foto]')].reduce((suma, otra) => suma + otra.files.length, 0);
    const elegidas = campo.querySelector('[data-fotos-elegidas]');
    elegidas.textContent = (total === 1 ? '1 foto elegida' : `${total} fotos elegidas`)
      + (total > 3 ? '. Cada prenda puede tener hasta 3 fotos.' : '');
    elegidas.hidden = total === 0;
    campo.querySelector('[data-sin-foto]')?.toggleAttribute('hidden', total > 0);

    const formulario = entrada.closest('form[data-subir-fotos]');
    if (formulario && total > 0) {
      formulario.requestSubmit();
    }
  });
}

// HU-18: tocar una foto la muestra grande en la misma pantalla (CA-18.2). Sin JavaScript, el enlace abre la foto completa.
function visorDeFotos() {
  const visor = document.querySelector('[data-visor]');
  if (!visor || typeof visor.showModal !== 'function') {
    return;
  }

  const imagen = visor.querySelector('[data-visor-imagen]');

  document.addEventListener('click', (evento) => {
    const enlace = evento.target.closest('a[data-ampliar]');
    if (!enlace) {
      return;
    }

    evento.preventDefault();
    imagen.src = enlace.href;
    imagen.alt = enlace.querySelector('img')?.alt ?? '';
    visor.showModal();
  });

  // Tocar fuera de la foto también la cierra; Escape la cierra por ser un diálogo
  visor.addEventListener('click', (evento) => {
    if (evento.target === visor) {
      visor.close();
    }
  });
}

// Deja el lado mayor en 1.600 px, como el servidor. Si el navegador no puede, envía la foto como está.
async function reducirFotos(entrada) {
  if (typeof DataTransfer === 'undefined' || typeof createImageBitmap === 'undefined') {
    return;
  }

  const reducidas = new DataTransfer();
  for (const archivo of entrada.files) {
    reducidas.items.add(await reducirFoto(archivo));
  }
  entrada.files = reducidas.files;
}

async function reducirFoto(archivo) {
  try {
    const imagen = await createImageBitmap(archivo, { imageOrientation: 'from-image' });
    const escala = Math.min(1, 1600 / Math.max(imagen.width, imagen.height));
    const lienzo = document.createElement('canvas');
    lienzo.width = Math.round(imagen.width * escala);
    lienzo.height = Math.round(imagen.height * escala);
    lienzo.getContext('2d').drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
    const reducida = await new Promise((listo) => lienzo.toBlob(listo, 'image/jpeg', 0.85));

    return reducida ? new File([reducida], `${archivo.name.replace(/\.[^.]+$/, '')}.jpg`, { type: 'image/jpeg' }) : archivo;
  } catch {
    return archivo;
  }
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
      const botones = [...formulario.querySelectorAll('button[type="submit"]')];
      // PT-13: «Guardar cambios» está en las acciones fijas, fuera del formulario, y lo nombra con form=
      if (formulario.id) {
        botones.push(...document.querySelectorAll(`button[type="submit"][form="${formulario.id}"]`));
      }
      // Después de que el navegador arma el envío: un botón deshabilitado antes no manda su valor (PT-11 envía estado con el botón)
      setTimeout(() => {
        botones.forEach((boton) => {
          boton.disabled = true;
        });
      }, 0);
    });
  });
}
