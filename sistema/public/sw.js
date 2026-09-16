// Trabajador de servicio de El-taller-ines (ADR-006, RNF-35).
// Solo guarda lo que no cambia y no tiene datos del taller: los estilos, el guion, las fuentes, los íconos y la página sin conexión.
// Nunca guarda páginas ni fotos: un celular perdido y sin conexión no debe mostrar información de clientes (RNF-25).
const CACHE = 'taller-v1';
const BASE = new URL('./', self.registration.scope);
const SIN_CONEXION = new URL('sin-conexion.html', BASE).pathname;
const DE_ENTRADA = [
  SIN_CONEXION,
  new URL('css/estilos.css', BASE).pathname,
  new URL('js/app.js', BASE).pathname,
  new URL('fuentes/atkinson-hyperlegible-next-latin.woff2', BASE).pathname,
  new URL('fuentes/atkinson-hyperlegible-mono-latin.woff2', BASE).pathname,
  new URL('iconos/icono-192.png', BASE).pathname,
  new URL('iconos/icono-512.png', BASE).pathname,
];

self.addEventListener('install', (evento) => {
  evento.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(DE_ENTRADA)).then(() => self.skipWaiting()));
});

// Al cambiar el nombre del caché, la versión anterior se borra
self.addEventListener('activate', (evento) => {
  evento.waitUntil(
    caches.keys()
      .then((nombres) => Promise.all(nombres.filter((nombre) => nombre !== CACHE).map((nombre) => caches.delete(nombre))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (evento) => {
  const solicitud = evento.request;
  if (solicitud.method !== 'GET' || new URL(solicitud.url).origin !== self.location.origin) {
    return;
  }

  // Las páginas se piden siempre a la red. Sin red, la página que explica que hace falta internet
  if (solicitud.mode === 'navigate') {
    evento.respondWith(fetch(solicitud).catch(() => caches.match(SIN_CONEXION)));
    return;
  }

  // Estilos, guion, fuentes e íconos: primero la red, para que un despliegue nuevo se vea enseguida.
  // El caché es el respaldo de cuando no hay internet, y se actualiza con lo que responda la red.
  const ruta = new URL(solicitud.url).pathname;
  if (DE_ENTRADA.includes(ruta)) {
    evento.respondWith(
      fetch(solicitud)
        .then((respuesta) => {
          if (respuesta.ok) {
            const copia = respuesta.clone();
            caches.open(CACHE).then((cache) => cache.put(solicitud, copia));
          }
          return respuesta;
        })
        .catch(() => caches.match(solicitud)),
    );
  }
});
