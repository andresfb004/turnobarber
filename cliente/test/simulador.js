// Simula a Telegram: entrega mensajes y toques de botones al bot y guarda lo que el bot
// le habría respondido al cliente, sin conectarse a los servidores de Telegram.
process.env.NODE_ENV = 'test';

const { Telegram } = require('telegraf');
const { crearBot } = require('../src/bot');

function crearSimulador() {
  const bot = crearBot('123456:TOKEN-DE-PRUEBA');
  bot.botInfo = { id: 1, is_bot: true, first_name: 'Barbería', username: 'barberia_test_bot' };

  const llamadas = [];
  let updateId = 0;

  // Telegraf crea una instancia de Telegram por actualización: se intercepta en el prototipo.
  Telegram.prototype.callApi = async (metodo, datos) => {
    llamadas.push({ metodo, datos });
    if (metodo === 'answerCallbackQuery') return true;
    return { message_id: llamadas.length, date: 0, chat: { id: datos.chat_id }, text: datos.text };
  };

  const usuario = (id, nombre) => ({ id, is_bot: false, first_name: nombre });
  const chat = (id) => ({ id, type: 'private' });

  async function entregar(update) {
    const desde = llamadas.length;
    await bot.handleUpdate({ update_id: ++updateId, ...update });
    // Solo lo que el cliente ve: se ignora la respuesta al reloj de carga del botón.
    return llamadas.slice(desde).filter((l) => l.metodo !== 'answerCallbackQuery');
  }

  return {
    bot,
    llamadas,
    texto: (id, nombre, texto) =>
      entregar({
        message: {
          message_id: updateId + 1,
          date: 0,
          chat: chat(id),
          from: usuario(id, nombre),
          text: texto,
          ...(texto.startsWith('/') ? { entities: [{ type: 'bot_command', offset: 0, length: texto.split(' ')[0].length }] } : {}),
        },
      }),
    contacto: (id, nombre, telefono, propietario = id) =>
      entregar({
        message: {
          message_id: updateId + 1,
          date: 0,
          chat: chat(id),
          from: usuario(id, nombre),
          contact: { phone_number: telefono, first_name: nombre, user_id: propietario },
        },
      }),
    boton: (id, nombre, dato) =>
      entregar({
        callback_query: {
          id: `cb${updateId + 1}`,
          from: usuario(id, nombre),
          chat_instance: 'prueba',
          data: dato,
          message: { message_id: 99, date: 0, chat: chat(id), text: 'mensaje anterior' },
        },
      }),
  };
}

// Botones (callback_data) de la última respuesta que tenga teclado.
function botones(respuestas) {
  const conTeclado = [...respuestas].reverse().find((r) => r.datos.reply_markup?.inline_keyboard);
  return conTeclado ? conTeclado.datos.reply_markup.inline_keyboard.flat().map((b) => b.callback_data) : [];
}

const textos = (respuestas) => respuestas.map((r) => r.datos.text).join('\n---\n');

module.exports = { crearSimulador, botones, textos };
