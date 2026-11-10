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
  // Cada cuánto el bot revisa si hay recordatorios por enviar.
  minutosRecordatorios: Number(process.env.MINUTOS_RECORDATORIOS || 10),
  // Días hábiles que se ofrecen para agendar.
  diasParaAgendar: Number(process.env.DIAS_PARA_AGENDAR || 7),
};
