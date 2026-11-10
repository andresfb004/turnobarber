// Cliente HTTP de la API de Laravel. Maneja los tres casos que pide el proyecto:
// sin conexión, token vencido (401) y regla de negocio rechazada (422).
const axios = require('axios');
const config = require('./config');

const http = axios.create({
  baseURL: config.apiUrl,
  timeout: 8000,
  headers: { Accept: 'application/json' },
});

let token = null;
let loginEnCurso = null;

// Si varias peticiones reciben 401 a la vez, todas esperan el mismo inicio de sesión.
function iniciarSesion() {
  if (!loginEnCurso) {
    loginEnCurso = http
      .post('/login', {
        email: config.botEmail,
        password: config.botPassword,
        device_name: 'bot-telegram',
      })
      .then(({ data }) => {
        token = data.token;
      })
      .finally(() => {
        loginEnCurso = null;
      });
  }

  return loginEnCurso;
}

/**
 * Llama a la API y nunca lanza excepciones: devuelve
 * { ok: true, status, data } o { ok: false, tipo, status, mensaje, errores }.
 * tipo: 'offline' | 'validacion' | 'no_encontrado' | 'error'.
 */
async function llamar(metodo, url, datos, reintento = true) {
  try {
    if (!token) await iniciarSesion();

    const respuesta = await http.request({
      method: metodo,
      url,
      data: datos,
      headers: { Authorization: `Bearer ${token}` },
    });

    return { ok: true, status: respuesta.status, data: respuesta.data };
  } catch (error) {
    return manejarError(error, () => llamar(metodo, url, datos, false), reintento);
  }
}

async function manejarError(error, reintentar, reintento) {
  if (!error.response) {
    console.warn(`[api] Sin conexión con la API: ${error.code || error.message}`);
    return { ok: false, tipo: 'offline' };
  }

  const { status, data } = error.response;

  if (status === 401 && reintento) {
    // Token vencido o borrado: se inicia sesión otra vez y se repite la petición una vez.
    token = null;
    return reintentar();
  }

  if (status === 422) {
    const errores = data.errors || {};
    const primero = Object.values(errores).flat()[0];
    return { ok: false, tipo: 'validacion', status, mensaje: primero || data.message, errores };
  }

  if (status === 404) {
    return { ok: false, tipo: 'no_encontrado', status, mensaje: data.message };
  }

  console.error(`[api] Error ${status}: ${data?.message || 'sin mensaje'}`);
  return { ok: false, tipo: 'error', status, mensaje: data?.message };
}

module.exports = {
  llamar,
  // Atajos con nombre para cada endpoint que usa el bot.
  vincularCliente: (datos) => llamar('post', '/clientes/vincular', datos),
  profesionales: () => llamar('get', '/profesionales'),
  turnos: (profesionalId, fecha) => llamar('get', `/turnos?profesional=${profesionalId}&fecha=${fecha}`),
  agendar: (datos) => llamar('post', '/citas', datos),
  citasDelCliente: (telegramId) => llamar('get', `/clientes/${telegramId}/citas`),
  cancelar: (citaId, telegramId) => llamar('patch', `/citas/${citaId}/cancelar`, { telegram_id: telegramId }),
  recordatoriosPendientes: () => llamar('get', '/recordatorios/pendientes'),
  marcarRecordatorioEnviado: (citaId) => llamar('patch', `/recordatorios/${citaId}/enviado`),
  // Solo para pruebas: permite simular un token vencido.
  _olvidarToken: () => {
    token = null;
  },
  _fijarToken: (valor) => {
    token = valor;
  },
};
