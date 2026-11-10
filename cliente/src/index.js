const config = require('./config');
const { crearBot } = require('./bot');
const { iniciarRecordatorios } = require('./recordatorios');

async function main() {
  const bot = crearBot(config.botToken);

  await bot.telegram.setMyCommands([
    { command: 'start', description: 'Empezar o registrarme' },
    { command: 'agendar', description: 'Agendar una cita' },
    { command: 'citas', description: 'Ver mis próximas citas' },
    { command: 'menu', description: 'Mostrar el menú' },
  ]);

  const recordatorios = iniciarRecordatorios(bot.telegram);

  const detener = (senal) => {
    clearInterval(recordatorios);
    bot.stop(senal);
  };
  process.once('SIGINT', () => detener('SIGINT'));
  process.once('SIGTERM', () => detener('SIGTERM'));

  console.log(`Bot iniciado. API: ${config.apiUrl}`);

  // Modo polling: el bot le pregunta a Telegram por mensajes nuevos, sin necesitar URL pública.
  await bot.launch();
}

main().catch((error) => {
  console.error('No se pudo iniciar el bot:', error.message);
  process.exit(1);
});
