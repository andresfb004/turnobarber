// Intermediario entre el túnel público y Ollama. Ollama no tiene contraseña, así que nunca se
// expone directamente: aquí se exige la clave y solo se deja pasar la petición de chat.
const http = require('node:http');
const crypto = require('node:crypto');

const MAX_CUERPO = 512 * 1024;
const MAX_SIMULTANEAS = 2;
const ESPERA_MODELO_MS = 90000;

const huella = (texto) => crypto.createHash('sha256').update(texto).digest();
const claveValida = (cabecera, clave) => crypto.timingSafeEqual(huella(cabecera || ''), huella(`Bearer ${clave}`));

async function leerCuerpo(req) {
  const partes = [];
  let tamano = 0;

  for await (const parte of req) {
    tamano += parte.length;
    if (tamano > MAX_CUERPO) return null;
    partes.push(parte);
  }

  return Buffer.concat(partes).toString();
}

/**
 * @param {{ clave: string, destino: string, modelo: string }} opciones
 * @returns {http.Server}
 */
function crearProxy({ clave, destino, modelo }) {
  let enCurso = 0;

  return http.createServer(async (req, res) => {
    const responder = (codigo, cuerpo) => {
      res.writeHead(codigo, { 'Content-Type': 'application/json', Connection: 'close' });
      res.end(typeof cuerpo === 'string' ? cuerpo : JSON.stringify(cuerpo));
    };

    if (!claveValida(req.headers.authorization, clave)) return responder(401, { error: 'Clave inválida.' });
    if (req.method !== 'POST' || req.url !== '/api/chat') return responder(404, { error: 'Ruta no disponible.' });
    if (enCurso >= MAX_SIMULTANEAS) return responder(503, { error: 'El modelo está ocupado.' });

    enCurso += 1;
    try {
      const texto = await leerCuerpo(req);
      if (texto === null) return responder(413, { error: 'Petición demasiado grande.' });

      let peticion;
      try {
        peticion = JSON.parse(texto);
      } catch {
        return responder(400, { error: 'JSON inválido.' });
      }

      // El modelo lo decide este equipo, no quien hace la petición.
      const respuesta = await fetch(`${destino}/api/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...peticion, model: modelo, stream: false }),
        signal: AbortSignal.timeout(ESPERA_MODELO_MS),
      });

      return responder(respuesta.status, await respuesta.text());
    } catch {
      return responder(502, { error: 'El modelo no respondió.' });
    } finally {
      enCurso -= 1;
    }
  });
}

module.exports = { crearProxy };
