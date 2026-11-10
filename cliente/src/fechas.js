// Fechas en la zona horaria de la barbería, sin depender de la zona del servidor donde corra el bot.
const config = require('./config');

const DIAS = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

// 'YYYY-MM-DD' de hoy en la zona horaria configurada.
function hoy(ahora = new Date()) {
  return new Intl.DateTimeFormat('en-CA', { timeZone: config.zonaHoraria }).format(ahora);
}

/**
 * Los próximos `cantidad` días de lunes a sábado, empezando hoy.
 * Los festivos los quita la API con la tabla de cierres (devuelve 0 turnos).
 */
function proximosDiasHabiles(cantidad, ahora = new Date()) {
  const [anio, mes, dia] = hoy(ahora).split('-').map(Number);
  const dias = [];

  for (let i = 0; dias.length < cantidad; i++) {
    const fecha = new Date(Date.UTC(anio, mes - 1, dia + i));

    if (fecha.getUTCDay() === 0) continue;

    dias.push({
      iso: fecha.toISOString().slice(0, 10),
      texto: i === 0 ? 'Hoy' : i === 1 ? 'Mañana' : `${DIAS[fecha.getUTCDay()]} ${fecha.getUTCDate()} ${MESES[fecha.getUTCMonth()]}`,
    });
  }

  return dias;
}

module.exports = { hoy, proximosDiasHabiles };
