// Valida los mockups contra las historias de usuario, genera su galería y toma las capturas.
//
// Uso:
//   node scripts/generar_mockups.mjs                 valida, genera la galería y el inventario, y toma las capturas
//   node scripts/generar_mockups.mjs --sin-capturas  solo valida y genera
//
// Cada pantalla (docs/03-diseno/mockups/pt-*.html) declara en su <head>:
//   <meta name="pantalla" content="PT-06">
//   <meta name="historias" content="HU-07, HU-09">
//   <meta name="criterios" content="CA-07.1">
// Las capturas se toman con Edge o Chrome sin interfaz, por su protocolo de depuración,
// a 390 px de ancho (un celular común) y con el alto completo de la página.

import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const RAIZ = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const CARPETA = join(RAIZ, 'docs', '03-diseno', 'mockups');
const CAPTURAS = join(CARPETA, 'capturas');
const HISTORIAS = join(RAIZ, 'docs', '02-requisitos', 'historias-de-usuario.md');
const LEEME = join(CARPETA, 'README.md');

const ANCHO = 390;
const ALTO_MINIMO = 844;
const ESCALA = 2;
const NAVEGADORES = [
  process.env.NAVEGADOR,
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
];

const esperar = (ms) => new Promise((ok) => setTimeout(ok, ms));
const escapar = (texto) => texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const lista = (valor) => (valor ?? '').split(',').map((c) => c.trim()).filter(Boolean);

// --- Lectura y validación ---------------------------------------------------------------------

function leerHistorias() {
  const texto = readFileSync(HISTORIAS, 'utf8');
  const historias = new Map([...texto.matchAll(/^### (HU-\d+) · (.+)$/gm)].map((m) => [m[1], m[2].trim()]));
  const criterios = new Set([...texto.matchAll(/\*\*(CA-\d+\.\d+)\*\*/g)].map((m) => m[1]));
  return { historias, criterios };
}

function leerPantallas() {
  return readdirSync(CARPETA)
    .filter((archivo) => /^pt-\d+-.+\.html$/.test(archivo))
    .sort()
    .map((archivo) => {
      const html = readFileSync(join(CARPETA, archivo), 'utf8');
      const meta = (nombre) => html.match(new RegExp(`<meta name="${nombre}" content="([^"]*)">`))?.[1];
      const titulo = html.match(/<title>(.*?)<\/title>/)?.[1] ?? '';
      return {
        archivo,
        codigo: meta('pantalla'),
        nombre: titulo.split(' · ').slice(1).join(' · '),
        historias: lista(meta('historias')),
        criterios: lista(meta('criterios')),
        enlaces: [...html.matchAll(/href="([^"#:]+\.html)"/g)].map((m) => m[1]),
      };
    });
}

function validar(pantallas, { historias, criterios }) {
  const errores = [];
  const codigos = new Set();
  for (const p of pantallas) {
    if (!p.codigo) errores.push(`${p.archivo}: falta <meta name="pantalla">`);
    else if (codigos.has(p.codigo)) errores.push(`${p.archivo}: el código ${p.codigo} está repetido`);
    codigos.add(p.codigo);
    if (!p.archivo.startsWith(p.codigo?.toLowerCase() ?? '-')) errores.push(`${p.archivo}: el nombre del archivo no empieza por ${p.codigo}`);
    if (!p.nombre) errores.push(`${p.archivo}: el <title> debe ser «PT-xx · Nombre»`);
    if (!p.historias.length) errores.push(`${p.archivo}: no declara historias`);
    for (const hu of p.historias) if (!historias.has(hu)) errores.push(`${p.archivo}: ${hu} no existe`);
    for (const ca of p.criterios) {
      if (!criterios.has(ca)) errores.push(`${p.archivo}: ${ca} no existe`);
      else if (!p.historias.includes(`HU-${ca.slice(3, 5)}`) && !pantallas.some((q) => q.historias.includes(`HU-${ca.slice(3, 5)}`)))
        errores.push(`${p.archivo}: ${ca} es de una historia que ninguna pantalla muestra`);
    }
    for (const enlace of p.enlaces) if (!existsSync(join(CARPETA, enlace))) errores.push(`${p.archivo}: enlaza a ${enlace}, que no existe`);
  }
  const cubiertas = new Set(pantallas.flatMap((p) => p.historias));
  const sinPantalla = [...historias.keys()].filter((hu) => !cubiertas.has(hu));
  if (sinPantalla.length) errores.push(`Historias sin pantalla: ${sinPantalla.join(', ')}`);
  return errores;
}

// --- Galería e inventario ---------------------------------------------------------------------

function generarGaleria(pantallas, { historias }) {
  const tarjetas = pantallas.map((p) => `
    <article class="pantalla">
      <header>
        <p class="codigo">${p.codigo}</p>
        <h2>${escapar(p.nombre)}</h2>
        <p class="hu">${p.historias.map((hu) => `<span title="${escapar(historias.get(hu) ?? '')}">${hu}</span>`).join('')}</p>
        <p class="enlaces"><a href="${p.archivo}">Abrir sola</a> · <a href="capturas/${p.archivo.replace('.html', '.png')}">Captura</a></p>
      </header>
      <div class="telefono"><iframe src="${p.archivo}" title="${p.codigo} · ${escapar(p.nombre)}" loading="lazy"></iframe></div>
    </article>`).join('');

  return `<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mockups · El-taller-ines</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible+Next:wght@400;700;800&family=Atkinson+Hyperlegible+Mono:wght@500;700&display=swap">
<style>
  :root { --fondo: #e9ebf2; --papel: #fff; --tinta: #161b2e; --tinta-2: #4b5470; --primario: #2a44a8; --tiza: #f2c23d; }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--fondo); color: var(--tinta); font: 16px/1.45 "Atkinson Hyperlegible Next", system-ui, sans-serif; }
  .pagina { max-width: 1400px; margin: 0 auto; padding: 32px 20px 56px; display: flex; flex-direction: column; gap: 28px; }
  h1 { margin: 0; font-size: 34px; line-height: 1.1; }
  .intro { margin: 8px 0 0; max-width: 70ch; color: var(--tinta-2); }
  .rejilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(410px, 1fr)); gap: 28px 24px; }
  .pantalla { display: flex; flex-direction: column; gap: 10px; }
  .pantalla header { display: flex; flex-direction: column; gap: 4px; }
  .codigo { margin: 0; font-family: "Atkinson Hyperlegible Mono", monospace; font-weight: 700; font-size: 14px; }
  .codigo::before { content: ""; display: inline-block; width: 10px; height: 10px; margin-right: 6px; border-radius: 50%; background: var(--tiza); }
  .pantalla h2 { margin: 0; font-size: 20px; }
  .hu { margin: 0; display: flex; flex-wrap: wrap; gap: 6px; }
  .hu span { padding: 1px 8px; border-radius: 999px; background: #dfe4f5; color: var(--primario); font-size: 13px; font-weight: 700; }
  .enlaces { margin: 0; font-size: 14px; color: var(--tinta-2); }
  .enlaces a { color: inherit; }
  .telefono { width: 410px; max-width: 100%; padding: 10px; border-radius: 34px; background: #161b2e; overflow-x: auto; }
  .telefono iframe { display: block; width: 390px; height: 760px; border: 0; border-radius: 24px; background: #f3f4f8; }
  a:focus-visible { outline: 3px solid var(--primario); outline-offset: 2px; }
  @media (max-width: 480px) { .rejilla { grid-template-columns: 1fr; } .telefono { padding: 6px; border-radius: 26px; } }
</style>
</head>
<body>
<div class="pagina">
  <header>
    <h1>Mockups de El-taller-ines</h1>
    <p class="intro">${pantallas.length} pantallas navegables, cada una con las historias de usuario que muestra. Se generan con <code>node scripts/generar_mockups.mjs</code>; el detalle está en <a href="README.md">README.md</a>.</p>
  </header>
  <div class="rejilla">${tarjetas}
  </div>
</div>
</body>
</html>
`;
}

function actualizarInventario(pantallas) {
  const filas = pantallas.map((p) =>
    `| **${p.codigo}** | [${p.nombre}](${p.archivo}) | ${p.historias.join(', ')} | ${p.criterios.join(', ') || '—'} |`);
  const galeria = [];
  for (let i = 0; i < pantallas.length; i += 3) {
    const grupo = pantallas.slice(i, i + 3);
    const celdas = grupo.map((p) => `<td valign="top" width="33%"><img src="capturas/${p.archivo.replace('.html', '.png')}" width="240" alt="${p.codigo} · ${escapar(p.nombre)}"><br><b>${p.codigo}</b> · ${escapar(p.nombre)}</td>`);
    galeria.push(`<tr>${celdas.join('')}</tr>`);
  }
  const bloque = [
    '<!-- inventario:inicio -->',
    '',
    `**${pantallas.length} pantallas.** Esta sección la genera el script; no se edita a mano.`,
    '',
    '| Código | Pantalla | Historias | Criterios que muestra |',
    '| --- | --- | --- | --- |',
    ...filas,
    '',
    '### Galería',
    '',
    `<table>${galeria.join('')}</table>`,
    '',
    '<!-- inventario:fin -->',
  ].join('\n');
  const texto = readFileSync(LEEME, 'utf8');
  const nuevo = texto.replace(/<!-- inventario:inicio -->[\s\S]*<!-- inventario:fin -->/, bloque);
  if (nuevo === texto && !texto.includes('<!-- inventario:inicio -->')) throw new Error('README.md no tiene los marcadores del inventario');
  writeFileSync(LEEME, nuevo);
}

// --- Capturas con el protocolo de depuración del navegador ------------------------------------

async function abrirNavegador() {
  const ruta = NAVEGADORES.find((r) => r && existsSync(r));
  if (!ruta) throw new Error('No se encontró Edge ni Chrome; indica la ruta en la variable NAVEGADOR');
  const perfil = mkdtempSync(join(tmpdir(), 'mockups-'));
  const proceso = spawn(ruta, [
    '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run', '--no-default-browser-check',
    '--disable-extensions', '--remote-debugging-port=0', `--user-data-dir=${perfil}`, 'about:blank',
  ], { stdio: 'ignore' });

  const archivoPuerto = join(perfil, 'DevToolsActivePort');
  let puerto;
  for (let i = 0; i < 150 && !puerto; i++) {
    if (existsSync(archivoPuerto)) puerto = readFileSync(archivoPuerto, 'utf8').split('\n')[0].trim() || undefined;
    if (!puerto) await esperar(100);
  }
  if (!puerto) throw new Error('El navegador no abrió el puerto de depuración');

  let pagina;
  for (let i = 0; i < 50 && !pagina; i++) {
    try { pagina = (await (await fetch(`http://127.0.0.1:${puerto}/json/list`)).json()).find((o) => o.type === 'page'); }
    catch { await esperar(100); }
  }
  const ws = new WebSocket(pagina.webSocketDebuggerUrl);
  await new Promise((ok, mal) => { ws.onopen = ok; ws.onerror = mal; });

  let siguiente = 0;
  const pendientes = new Map();
  const eventos = new Map();
  ws.onmessage = (evento) => {
    const mensaje = JSON.parse(evento.data);
    if (mensaje.id && pendientes.has(mensaje.id)) {
      const { ok, mal } = pendientes.get(mensaje.id);
      pendientes.delete(mensaje.id);
      if (mensaje.error) mal(new Error(mensaje.error.message)); else ok(mensaje.result);
    } else if (mensaje.method && eventos.has(mensaje.method)) {
      eventos.get(mensaje.method)(mensaje.params);
    }
  };
  const enviar = (method, params = {}) => new Promise((ok, mal) => {
    const id = ++siguiente;
    pendientes.set(id, { ok, mal });
    ws.send(JSON.stringify({ id, method, params }));
  });
  const cerrar = async () => {
    ws.close();
    proceso.kill();
    await esperar(500);
    try { rmSync(perfil, { recursive: true, force: true }); } catch { /* el navegador puede tardar en soltar el perfil */ }
  };
  return { enviar, eventos, cerrar };
}

async function capturar(pantallas) {
  mkdirSync(CAPTURAS, { recursive: true });
  const { enviar, eventos, cerrar } = await abrirNavegador();
  try {
    await enviar('Page.enable');
    for (const p of pantallas) {
      await enviar('Emulation.setDeviceMetricsOverride', { width: ANCHO, height: ALTO_MINIMO, deviceScaleFactor: ESCALA, mobile: true });
      const cargada = new Promise((ok) => eventos.set('Page.loadEventFired', ok));
      await enviar('Page.navigate', { url: pathToFileURL(join(CARPETA, p.archivo)).href });
      await cargada;
      const { result } = await enviar('Runtime.evaluate', {
        expression: 'document.fonts.ready.then(() => Math.ceil(document.documentElement.scrollHeight))',
        awaitPromise: true, returnByValue: true,
      });
      const alto = Math.max(ALTO_MINIMO, result.value);
      await enviar('Emulation.setDeviceMetricsOverride', { width: ANCHO, height: alto, deviceScaleFactor: ESCALA, mobile: true });
      await enviar('Runtime.evaluate', { expression: 'new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)))', awaitPromise: true });
      const { data } = await enviar('Page.captureScreenshot', { format: 'png' });
      writeFileSync(join(CAPTURAS, p.archivo.replace('.html', '.png')), Buffer.from(data, 'base64'));
      console.log(`${p.codigo}: ${ANCHO} × ${alto} px`);
    }
  } finally {
    await cerrar();
  }
}

// --- Principal --------------------------------------------------------------------------------

const requisitos = leerHistorias();
const pantallas = leerPantallas();
const errores = validar(pantallas, requisitos);
if (errores.length) {
  console.error('Errores:');
  for (const error of errores) console.error(`- ${error}`);
  process.exit(1);
}
const cubiertas = new Set(pantallas.flatMap((p) => p.historias));
console.log(`${pantallas.length} pantallas · ${cubiertas.size} de ${requisitos.historias.size} historias con pantalla`);

writeFileSync(join(CARPETA, 'index.html'), generarGaleria(pantallas, requisitos));
actualizarInventario(pantallas);
console.log('Galería e inventario generados');

if (!process.argv.includes('--sin-capturas')) await capturar(pantallas);
