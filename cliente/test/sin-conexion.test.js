// Con la API caída el bot avisa al cliente y no se cae. Puerto 9: nadie escucha ahí.
process.env.API_URL = 'http://127.0.0.1:9/api';
process.env.BOT_EMAIL = 'bot@turnobarber.test';
process.env.BOT_PASSWORD = 'password';

const test = require('node:test');
const assert = require('node:assert/strict');
const { crearSimulador, botones, textos } = require('./simulador');

test('sin conexión con la API avisa al cliente y ofrece reintentar', async () => {
  const sim = crearSimulador();

  const inicio = await sim.texto(555, 'Ana', '/start');
  assert.match(textos(inicio), /no está disponible/);

  const agendar = await sim.boton(555, 'Ana', 'agendar');
  assert.match(textos(agendar), /no está disponible/);
  assert.deepEqual(botones(agendar), ['agendar', 'menu']);
});
