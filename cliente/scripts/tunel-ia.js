// Deja el modelo de este Mac disponible para el bot en Railway: Ollama + intermediario con clave
// + túnel de Cloudflare. Se detiene con Ctrl+C.
const { spawn } = require('node:child_process');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const { crearProxy } = require('./proxy-ia');

const ARCHIVO_ENV = path.join(__dirname, '..', '.env');
const OLLAMA_LOCAL = 'http://127.0.0.1:11434';
const PUERTO = 11435;

try {
  process.loadEnvFile(ARCHIVO_ENV);
} catch {
  // Sin archivo .env: se crea al guardar la clave.
}

const modelo = process.env.OLLAMA_MODELO || 'qwen3.5:9b';
const hijos = [];

function obtenerClave() {
  if (process.env.OLLAMA_CLAVE) return process.env.OLLAMA_CLAVE;

  const clave = crypto.randomBytes(32).toString('hex');
  fs.appendFileSync(ARCHIVO_ENV, `\nOLLAMA_CLAVE=${clave}\n`);
  return clave;
}

const ollamaResponde = () => fetch(`${OLLAMA_LOCAL}/api/tags`, { signal: AbortSignal.timeout(2000) })
  .then((r) => r.json())
  .catch(() => null);

async function asegurarOllama() {
  let estado = await ollamaResponde();

  if (!estado) {
    console.log('Iniciando Ollama…');
    hijos.push(spawn('ollama', ['serve'], { stdio: 'ignore' }));

    for (let intento = 0; intento < 30 && !estado; intento += 1) {
      await new Promise((listo) => setTimeout(listo, 1000));
      estado = await ollamaResponde();
    }
  }

  if (!estado) throw new Error('Ollama no arrancó. Revisa que esté instalado.');
  if (!estado.models.some((m) => m.name === modelo)) {
    throw new Error(`Falta el modelo ${modelo}. Descárgalo con: ollama pull ${modelo}`);
  }
}

function abrirTunel(clave) {
  const tunel = spawn('cloudflared', ['tunnel', '--no-autoupdate', '--url', `http://127.0.0.1:${PUERTO}`], {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
  hijos.push(tunel);

  let anunciado = false;
  const leer = (parte) => {
    const url = String(parte).match(/https:\/\/[a-z0-9-]+\.trycloudflare\.com/)?.[0];
    if (!url || anunciado) return;

    anunciado = true;
    console.log(`
Listo. Pon estas variables en el servicio bot-telegram de Railway:

  IA_PROVEEDOR=ollama
  OLLAMA_URL=${url}
  OLLAMA_CLAVE=${clave}
  OLLAMA_MODELO=${modelo}

La URL cambia cada vez que arrancas este comando; la clave no.
Deja esta ventana abierta y el Mac con la tapa abierta. Ctrl+C para detener.
`);
  };

  tunel.stdout.on('data', leer);
  tunel.stderr.on('data', leer);
  tunel.on('error', () => salir('No encontré cloudflared. Instálalo con: brew install cloudflared'));
  tunel.on('exit', () => salir('El túnel se cerró.'));
}

function salir(mensaje) {
  if (mensaje) console.log(mensaje);
  hijos.forEach((hijo) => hijo.removeAllListeners('exit').kill());
  process.exit(mensaje ? 1 : 0);
}

async function iniciar() {
  process.on('SIGINT', () => salir());
  process.on('SIGTERM', () => salir());

  await asegurarOllama();
  const clave = obtenerClave();

  // Evita que el Mac se duerma por inactividad mientras esto esté corriendo.
  hijos.push(spawn('caffeinate', ['-is', '-w', String(process.pid)], { stdio: 'ignore' }));

  crearProxy({ clave, destino: OLLAMA_LOCAL, modelo }).listen(PUERTO, '127.0.0.1', () => abrirTunel(clave));
}

iniciar().catch((error) => salir(error.message));
