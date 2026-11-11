// Proveedor de IA local con Ollama. Para cambiar de proveedor se escribe otro archivo con
// la misma función `conversar` y se selecciona en src/ia/index.js.
const axios = require('axios');
const config = require('../config');

const http = axios.create({
  baseURL: config.ollamaUrl,
  timeout: config.ollamaEsperaMs,
  // Solo hace falta cuando el modelo está detrás del túnel (scripts/tunel-ia.js).
  headers: config.ollamaClave ? { Authorization: `Bearer ${config.ollamaClave}` } : {},
});

/**
 * Envía la conversación al modelo.
 *
 * @param {{ sistema: string, mensajes: Array, herramientas: Array }} peticion
 * @returns {Promise<{ texto: string, llamadas: Array<{ nombre: string, argumentos: object }>, mensaje: object }>}
 */
async function conversar({ sistema, mensajes, herramientas }) {
  const { data } = await http.post('/api/chat', {
    model: config.ollamaModelo,
    stream: false,
    think: false,
    options: { temperature: 0.4 },
    messages: [{ role: 'system', content: sistema }, ...mensajes],
    tools: herramientas.map(({ nombre, descripcion, parametros }) => ({
      type: 'function',
      function: { name: nombre, description: descripcion, parameters: parametros },
    })),
  });

  return {
    texto: (data.message.content || '').trim(),
    llamadas: (data.message.tool_calls || []).map((llamada) => ({
      nombre: llamada.function.name,
      argumentos: llamada.function.arguments || {},
    })),
    mensaje: data.message,
  };
}

const resultadoHerramienta = (nombre, contenido) => ({ role: 'tool', tool_name: nombre, content: contenido });

module.exports = { conversar, resultadoHerramienta };
