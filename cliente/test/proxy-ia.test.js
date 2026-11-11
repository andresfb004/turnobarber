const test = require('node:test');
const assert = require('node:assert/strict');
const http = require('node:http');
const { crearProxy } = require('../scripts/proxy-ia');

const CLAVE = 'clave-de-prueba';
const recibidas = [];
let proxy;
let ollama;
let url;

const escuchar = (servidor) => new Promise((listo) => servidor.listen(0, '127.0.0.1', () => listo(servidor.address().port)));
const pedir = (ruta, { clave = CLAVE, metodo = 'POST', cuerpo = '{"model":"otro","messages":[]}' } = {}) => fetch(`${url}${ruta}`, {
  method: metodo,
  headers: clave ? { Authorization: `Bearer ${clave}` } : {},
  body: metodo === 'POST' ? cuerpo : undefined,
});

test.before(async () => {
  ollama = http.createServer((req, res) => {
    let cuerpo = '';
    req.on('data', (parte) => { cuerpo += parte; });
    req.on('end', () => {
      recibidas.push({ ruta: req.url, cuerpo: JSON.parse(cuerpo), autorizacion: req.headers.authorization });
      res.end('{"message":{"content":"hola"}}');
    });
  });
  const puertoOllama = await escuchar(ollama);

  proxy = crearProxy({ clave: CLAVE, destino: `http://127.0.0.1:${puertoOllama}`, modelo: 'modelo-fijo' });
  url = `http://127.0.0.1:${await escuchar(proxy)}`;
});

test.after(() => {
  proxy.close();
  ollama.close();
});

test('sin clave o con clave equivocada responde 401 y no llega a Ollama', async () => {
  const antes = recibidas.length;

  assert.equal((await pedir('/api/chat', { clave: null })).status, 401);
  assert.equal((await pedir('/api/chat', { clave: 'equivocada' })).status, 401);
  assert.equal(recibidas.length, antes);
});

test('solo deja pasar el chat', async () => {
  assert.equal((await pedir('/api/pull')).status, 404);
  assert.equal((await pedir('/api/delete')).status, 404);
  assert.equal((await pedir('/api/tags', { metodo: 'GET' })).status, 404);
});

test('reenvía el chat con el modelo del equipo y sin la clave', async () => {
  const respuesta = await pedir('/api/chat');

  assert.equal(respuesta.status, 200);
  assert.equal((await respuesta.json()).message.content, 'hola');

  const ultima = recibidas.at(-1);
  assert.equal(ultima.cuerpo.model, 'modelo-fijo');
  assert.equal(ultima.autorizacion, undefined);
});

test('rechaza JSON inválido y cuerpos enormes', async () => {
  assert.equal((await pedir('/api/chat', { cuerpo: 'no es json' })).status, 400);
  assert.equal((await pedir('/api/chat', { cuerpo: 'x'.repeat(600 * 1024) })).status, 413);
});
