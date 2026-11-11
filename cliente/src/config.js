// Lee la configuración del archivo .env (en local) o de las variables del sistema (en la nube).
try {
  process.loadEnvFile();
} catch {
  // Sin archivo .env: se usan las variables de entorno ya definidas.
}

const requeridas = ['BOT_TOKEN', 'API_URL', 'BOT_EMAIL', 'BOT_PASSWORD'];
const faltantes = requeridas.filter((nombre) => !process.env[nombre]);

if (faltantes.length > 0 && process.env.NODE_ENV !== 'test') {
  console.error(`Faltan variables de entorno: ${faltantes.join(', ')}. Revisa el archivo .env.`);
  process.exit(1);
}

module.exports = {
  botToken: process.env.BOT_TOKEN,
  apiUrl: (process.env.API_URL || '').replace(/\/+$/, ''),
  botEmail: process.env.BOT_EMAIL,
  botPassword: process.env.BOT_PASSWORD,
  zonaHoraria: process.env.ZONA_HORARIA || 'America/Bogota',
  // Nombre que ve el cliente en Telegram. Vacío: el bot conserva el nombre que le diste en @BotFather.
  nombreBarberia: (process.env.BARBERIA_NOMBRE || '').trim(),
  // A quién avisar cuando un cliente pide hablar con una persona (IDs de Telegram separados por coma).
  avisosTelegramIds: (process.env.AVISOS_TELEGRAM_IDS || '').split(',').map((id) => id.trim()).filter(Boolean),
  // Nombre de la persona que atiende, tal como lo verá el cliente.
  nombreEncargado: (process.env.ENCARGADO_NOMBRE || 'David').trim(),
  // Conversación con IA. Vacío: solo botones.
  iaProveedor: (process.env.IA_PROVEEDOR || '').trim(),
  ollamaUrl: process.env.OLLAMA_URL || 'http://127.0.0.1:11434',
  ollamaModelo: process.env.OLLAMA_MODELO || 'qwen3.5:9b',
  ollamaClave: (process.env.OLLAMA_CLAVE || '').trim(),
  ollamaEsperaMs: Number(process.env.OLLAMA_ESPERA_MS || 60000),
  iaMensajesPorHora: Number(process.env.IA_MENSAJES_POR_HORA || 30),
  iaDepurar: process.env.IA_DEPURAR === '1',
  // Cada cuánto el bot revisa si hay recordatorios por enviar.
  minutosRecordatorios: Number(process.env.MINUTOS_RECORDATORIOS || 10),
  // Días hábiles que se ofrecen para agendar.
  diasParaAgendar: Number(process.env.DIAS_PARA_AGENDAR || 7),
};
