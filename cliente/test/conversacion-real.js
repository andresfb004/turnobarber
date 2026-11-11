// Conversación de prueba con el modelo real. No es automática: sirve para leer cómo responde.
//   cd backend && php artisan serve --port=8001
//   ollama serve
//   node test/conversacion-real.js
process.env.API_URL ||= 'http://127.0.0.1:8001/api';
process.env.BOT_EMAIL ||= 'bot@turnobarber.test';
process.env.BOT_PASSWORD ||= 'password';
process.env.IA_PROVEEDOR = 'ollama';
process.env.BARBERIA_NOMBRE ||= 'David Orozco Barbería';
process.env.AVISOS_TELEGRAM_IDS = '999';
process.env.IA_DEPURAR = '1';

const { crearSimulador, botones, textos } = require('./simulador');

const ID = 800000000 + Math.floor(Math.random() * 9000000);
const mensajes = [
  'hola, será que mañana me puedo motilar por la tarde?',
  'agéndame a las 4 por favor',
  'mi cita está confirmada?',
  'Si confirmo',
  'mi cita está confirmada?',
  'muchas gracias David',
  'eres un bot?',
];

(async () => {
  const sim = crearSimulador();
  await sim.contacto(ID, 'Andrés', `+57 31${String(ID).slice(-8)}`);

  for (const texto of mensajes) {
    const inicio = Date.now();
    const r = texto === 'CONFIRMAR' ? await sim.boton(ID, 'Andrés', 'confirmar') : await sim.texto(ID, 'Andrés', texto);
    const visibles = r.filter((x) => x.metodo !== 'sendChatAction');
    console.log(`\n👤 ${texto}`);
    console.log(`🤖 ${textos(visibles)}`);
    if (botones(visibles).length) console.log(`   [botones: ${botones(visibles).join(', ')}]`);
    console.log(`   (${((Date.now() - inicio) / 1000).toFixed(1)} s)`);
  }
})();
