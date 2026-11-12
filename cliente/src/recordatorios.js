// Cada pocos minutos pregunta a la API qué citas empiezan pronto y le escribe a cada cliente.
// Laravel no conoce Telegram: el bot recoge los avisos y los marca como enviados.
const { Markup } = require('telegraf');
const api = require('./api');
const config = require('./config');
const { escapar } = require('./flujo');
const { toleranciaMinutos } = require('./negocio');

let revisando = false;

async function revisarRecordatorios(telegram) {
  // Evita dos revisiones a la vez si una se demora más que el intervalo.
  if (revisando) return { enviados: 0 };
  revisando = true;

  let enviados = 0;

  try {
    const resultado = await api.recordatoriosPendientes();

    if (!resultado.ok) {
      console.warn(`[recordatorios] No se pudo consultar la API (${resultado.tipo}).`);
      return { enviados };
    }

    for (const cita of resultado.data.data) {
      try {
        await telegram.sendMessage(
          cita.cliente.telegram_id,
          `⏰ <b>Recordatorio</b>\n\nTe esperamos hoy a las <b>${escapar(cita.hora_texto)}</b> con ${escapar(cita.profesional.nombre)}. Si se te hace tarde, te esperamos máximo ${toleranciaMinutos} minutos.`,
          {
            parse_mode: 'HTML',
            ...Markup.inlineKeyboard([
              [Markup.button.callback('✅ Asistiré', `asisto:${cita.id}`), Markup.button.callback('❌ Cancelar', `cancelar:${cita.id}`)],
            ]),
          },
        );
        enviados++;
      } catch (error) {
        // 403: el cliente bloqueó el bot. Se marca igual para no intentarlo cada 10 minutos.
        if (error.code !== 403) {
          console.error(`[recordatorios] No se pudo avisar la cita ${cita.id}: ${error.message}`);
          continue;
        }
      }

      await api.marcarRecordatorioEnviado(cita.id);
    }
  } finally {
    revisando = false;
  }

  return { enviados };
}

function iniciarRecordatorios(telegram) {
  const intervalo = config.minutosRecordatorios * 60 * 1000;

  revisarRecordatorios(telegram);
  return setInterval(() => revisarRecordatorios(telegram), intervalo);
}

module.exports = { revisarRecordatorios, iniciarRecordatorios };
