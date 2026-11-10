const { Telegraf } = require('telegraf');
const { registrarFlujo } = require('./flujo');

function crearBot(token) {
  const bot = new Telegraf(token);

  registrarFlujo(bot);

  // Un error inesperado en un manejador no debe tumbar el bot.
  bot.catch(async (error, ctx) => {
    console.error(`[bot] Error en la actualización ${ctx.update.update_id}:`, error);
    await ctx.reply('😕 Algo salió mal. Escribe /menu para empezar de nuevo.').catch(() => {});
  });

  return bot;
}

module.exports = { crearBot };
