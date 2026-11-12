// Prueba de punta a punta contra una API local de Laravel con los datos del seeder:
//   cd backend && php artisan migrate:fresh --seed && php artisan serve --port=8001
process.env.API_URL = process.env.TEST_API_URL || 'http://127.0.0.1:8001/api';
process.env.IA_PROVEEDOR = '';
process.env.BOT_EMAIL = 'bot@turnobarber.test';
process.env.BOT_PASSWORD = process.env.TEST_BOT_PASSWORD || 'password';

const test = require('node:test');
const assert = require('node:assert/strict');
const { crearSimulador, botones, textos } = require('./simulador');
const api = require('../src/api');
const { revisarRecordatorios } = require('../src/recordatorios');

const sim = crearSimulador();
const ANA = 700000000 + Math.floor(Math.random() * 9000000);
const LUIS = ANA + 1;
const BLOQUEADO = 900000001;
const telefono = (id) => `+57 31${String(id).slice(-8)}`;

// Recorre el flujo de agendar y devuelve las respuestas del último paso (confirmar).
async function agendarHasta(id, nombre, { turno } = {}) {
  let r = await sim.boton(id, nombre, 'agendar');
  const profesional = botones(r).find((b) => b.startsWith('prof:'));

  r = await sim.boton(id, nombre, profesional);
  // El segundo día ofrecido nunca es hoy, así que tiene turnos completos.
  const dia = botones(r).filter((b) => b.startsWith('dia:'))[1];

  r = await sim.boton(id, nombre, dia);
  const elegido = turno ?? botones(r).find((b) => b.startsWith('turno:'));
  assert.ok(elegido, `Debe haber turnos libres. Respuesta: ${textos(r)}`);

  r = await sim.boton(id, nombre, elegido);
  assert.match(textos(r), /Confirma tu cita/);

  return { respuestas: await sim.boton(id, nombre, 'confirmar'), turno: elegido };
}

test('un cliente nuevo debe compartir su número antes de agendar', async () => {
  const r = await sim.texto(ANA, 'Ana', '/start');

  assert.match(textos(r), /Compartir mi número|necesito tu número/);
  assert.equal(r[0].datos.reply_markup.keyboard[0][0].request_contact, true);
});

test('rechaza el contacto de otra persona', async () => {
  const r = await sim.contacto(ANA, 'Ana', telefono(ANA), 12345);

  assert.match(textos(r), /tu propio número/);
});

test('al compartir su número queda registrado y ve el menú', async () => {
  const r = await sim.contacto(ANA, 'Ana', telefono(ANA));

  assert.match(textos(r), /Ya estás registrado/);
  assert.deepEqual(botones(r), ['agendar', 'mis_citas', 'cancelar', 'servicios', 'humano']);

  const otraVez = await sim.texto(ANA, 'Ana', '/start');
  assert.match(textos(otraVez), /Hola de nuevo, Ana/);
});

test('agenda una cita con botones: profesional, día, turno y confirmar', async () => {
  const { respuestas } = await agendarHasta(ANA, 'Ana');

  assert.match(textos(respuestas), /¡Cita agendada!/);
});

test('RN2: si el turno se toma antes de confirmar, avisa y ofrece las horas que quedan', async () => {
  await sim.contacto(LUIS, 'Luis', telefono(LUIS));

  // Luis llega al resumen del mismo turno libre que va a tomar Ana.
  let r = await sim.boton(LUIS, 'Luis', 'agendar');
  r = await sim.boton(LUIS, 'Luis', botones(r).find((b) => b.startsWith('prof:')));
  r = await sim.boton(LUIS, 'Luis', botones(r).filter((b) => b.startsWith('dia:'))[1]);
  const turnoLibre = botones(r).find((b) => b.startsWith('turno:'));
  await sim.boton(LUIS, 'Luis', turnoLibre);

  // Ana lo agenda primero.
  const ana = await agendarHasta(ANA, 'Ana', { turno: turnoLibre });
  assert.match(textos(ana.respuestas), /¡Cita agendada!/);

  // Luis confirma tarde: 422 de la API, mensaje claro y nuevas horas.
  r = await sim.boton(LUIS, 'Luis', 'confirmar');
  assert.match(textos(r), /acaba de ser tomado/);
  assert.ok(!botones(r).includes(turnoLibre), 'El turno tomado ya no se ofrece.');
  assert.ok(botones(r).some((b) => b.startsWith('turno:')));
});

test('RN4: un cliente bloqueado recibe el mensaje de la API', async () => {
  const { respuestas } = await agendarHasta(BLOQUEADO, 'Bloqueado');

  assert.match(textos(respuestas), /bloqueada por inasistencias/);
});

test('muestra las próximas citas del cliente', async () => {
  const r = await sim.boton(ANA, 'Ana', 'mis_citas');

  assert.match(textos(r), /Tus próximas citas/);
  assert.match(textos(r), /a\. m\.|p\. m\./);
});

test('cancela una cita con confirmación', async () => {
  let r = await sim.boton(ANA, 'Ana', 'cancelar');
  const cita = botones(r).find((b) => b.startsWith('cancelar:'));

  r = await sim.boton(ANA, 'Ana', cita);
  assert.match(textos(r), /Seguro que quieres cancelar/);

  r = await sim.boton(ANA, 'Ana', cita.replace('cancelar:', 'cancelar_si:'));
  assert.match(textos(r), /Cita cancelada/);
});

test('401: con un token vencido vuelve a iniciar sesión sin que el cliente lo note', async () => {
  api._fijarToken('1|token-vencido');

  const r = await sim.boton(ANA, 'Ana', 'mis_citas');

  assert.match(textos(r), /Tus próximas citas/);
});

test('un texto libre muestra el menú', async () => {
  const r = await sim.texto(ANA, 'Ana', 'quiero un corte mañana');

  assert.match(textos(r), /No entendí/);
  assert.deepEqual(botones(r), ['agendar', 'mis_citas', 'cancelar', 'servicios', 'humano']);
});

test('muestra servicios, precios y formas de pago sin necesitar la IA', async () => {
  const r = await sim.boton(ANA, 'Ana', 'servicios');

  assert.match(textos(r), /Corte de caballero \(incluye barba\): <b>\$45\.000<\/b>/);
  assert.match(textos(r), /precios aproximados/);
  assert.match(textos(r), /Efectivo o transferencia/);
  assert.ok(botones(r).includes('agendar'));
});

test('envía los recordatorios pendientes y los marca como enviados', async () => {
  const pendientes = (await api.recordatoriosPendientes()).data.data;
  const enviados = [];
  const telegram = { sendMessage: async (chatId, texto) => enviados.push({ chatId, texto }) };

  await revisarRecordatorios(telegram);

  assert.equal(enviados.length, pendientes.length);
  enviados.forEach((m) => assert.match(m.texto, /Te esperamos hoy[\s\S]*máximo 25 minutos/));
  assert.equal((await api.recordatoriosPendientes()).data.data.length, 0);
});
