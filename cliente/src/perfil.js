// Perfil público del bot: lo que ve alguien antes de tocar "Iniciar". Un bot sin descripción
// parece sospechoso; con nombre, descripción y comandos claros el cliente sabe qué es.
const config = require('./config');

const nombre = () => config.nombreBarberia || 'la barbería';

function descripcionCorta() {
  return `Agenda tu cita en ${nombre()}: barbería y belleza, sin llamar ni esperar.`;
}

function descripcion() {
  return [
    `💈 Asistente de citas de ${nombre()}.`,
    '',
    'Con este bot puedes:',
    '• Agendar tu cita con el barbero o con la estilista',
    '• Ver y cancelar tus próximas citas',
    '• Recibir un recordatorio antes de tu turno',
    '',
    '🔒 Solo te pediremos tu número de celular para identificar tus citas. No lo compartimos con nadie.',
    '',
    'Toca "Iniciar" para empezar.',
  ].join('\n');
}

async function configurarPerfil(telegram) {
  const pasos = [
    ['comandos', () =>
      telegram.setMyCommands([
        { command: 'start', description: 'Empezar o registrarme' },
        { command: 'agendar', description: 'Agendar una cita' },
        { command: 'citas', description: 'Ver mis próximas citas' },
        { command: 'menu', description: 'Mostrar el menú' },
      ])],
    ['descripción', () => telegram.setMyDescription(descripcion())],
    ['descripción corta', () => telegram.setMyShortDescription(descripcionCorta())],
  ];

  if (config.nombreBarberia) {
    pasos.push(['nombre', () => telegram.setMyName(config.nombreBarberia.slice(0, 64))]);
  }

  // Si Telegram limita estos cambios (por ejemplo, el nombre tras varios reinicios),
  // el bot sigue funcionando igual: solo se registra el aviso.
  for (const [que, paso] of pasos) {
    try {
      await paso();
    } catch (error) {
      console.warn(`[perfil] No se pudo actualizar ${que}: ${error.description || error.message}`);
    }
  }
}

module.exports = { configurarPerfil, descripcion, descripcionCorta };
