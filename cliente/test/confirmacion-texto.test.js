// El error real que se vio en Telegram: el cliente escribe "si confirmo" en vez de tocar el botón.
process.env.API_URL = 'http://127.0.0.1:9/api';
process.env.IA_PROVEEDOR = 'ollama';

const test = require('node:test');
const assert = require('node:assert/strict');
const { crearSimulador, textos } = require('./simulador');
const api = require('../src/api');
const agente = require('../src/ia/agente');

const INICIO = '2026-11-12T16:00:00-05:00';
const profesional = { id: 1, nombre: 'Barbero', especialidad: 'barberia', especialidad_texto: 'Barbería' };
const citaCreada = { ok: true, data: { data: { profesional, fecha_texto: 'jueves 12 de noviembre', hora_texto: '4:00 p. m.' } } };

api.citasDelCliente = async () => ({ ok: true, data: { data: [] } });
api.profesionales = async () => ({ ok: true, data: { data: [profesional] } });
api.turnos = async () => ({ ok: true, data: { data: [{ inicio: INICIO, hora: '4:00 p. m.' }] } });

async function llegarAlResumen(sim, id) {
  await sim.boton(id, 'Ana', 'prof:1');
  await sim.boton(id, 'Ana', 'dia:2026-11-12:Jueves');
  await sim.boton(id, 'Ana', `turno:${INICIO}`);
}

test('escribir "si confirmo" crea la cita, igual que el botón', async () => {
  const sim = crearSimulador();
  let creadas = 0;
  api.agendar = async () => { creadas++; return citaCreada; };
  agente.responder = async () => assert.fail('No debe pasar por el modelo');

  await llegarAlResumen(sim, 501);
  const r = await sim.texto(501, 'Ana', 'Si confirmo');

  assert.equal(creadas, 1);
  assert.match(textos(r), /¡Cita agendada!/);
});

test('con un resumen pendiente, el modelo sabe que la cita no está agendada', async () => {
  const sim = crearSimulador();
  let creadas = 0;
  let opciones;
  api.agendar = async () => { creadas++; return citaCreada; };
  agente.responder = async (ctx, texto, acciones, o) => { opciones = o; return 'Todavía falta confirmar.'; };

  await llegarAlResumen(sim, 502);
  await sim.texto(502, 'Ana', 'a qué hora era?');

  assert.equal(creadas, 0);
  assert.equal(opciones.citaSinConfirmar, true);
});

test('la pregunta por su cita se responde con la API, no con el modelo', async () => {
  const sim = crearSimulador();
  agente.responder = async () => assert.fail('No debe pasar por el modelo');
  const original = api.citasDelCliente;
  api.citasDelCliente = async () => ({ ok: true, data: { data: [{ id: 7, fecha_texto: 'jueves 12', hora_texto: '4:00 p. m.', profesional }] } });

  const agendada = await sim.texto(505, 'Ana', 'mi cita está confirmada?');
  assert.match(textos(agendada), /Tus próximas citas/);

  api.citasDelCliente = original;
  await llegarAlResumen(sim, 506);
  const pendiente = await sim.texto(506, 'Ana', 'mi cita esta confirmada?');
  assert.match(textos(pendiente), /Todavía no está agendada/);
});

test('sin un resumen pendiente, un "si" no crea nada', async () => {
  const sim = crearSimulador();
  let creadas = 0;
  api.agendar = async () => { creadas++; return citaCreada; };
  agente.responder = async () => 'Con gusto.';

  await sim.texto(503, 'Ana', 'si');

  assert.equal(creadas, 0);
});

test('una respuesta normal no lleva el botón de una persona', async () => {
  const sim = crearSimulador();
  agente.responder = async () => 'Claro, mañana hay turnos.';

  const r = await sim.texto(504, 'Ana', 'tienes turnos mañana?');

  assert.equal(r.at(-1).datos.reply_markup, undefined);
});
