// Conversación del bot. El cliente nunca escribe texto libre: elige con botones,
// y cada botón lleva un dato exacto (por ejemplo "prof:2" o "dia:2026-11-11").
const { Markup } = require('telegraf');
const api = require('./api');
const config = require('./config');
const { proximosDiasHabiles } = require('./fechas');
const agente = require('./ia/agente');
const negocio = require('./negocio');

// Paso en el que va cada cliente: chatId -> { profesional, fecha, fechaTexto, inicio }.
// Vive en memoria; si el bot se reinicia, el cliente vuelve a empezar desde el menú.
const estado = new Map();

const boton = (texto, dato) => Markup.button.callback(texto, dato);
const volverAlMenu = [boton('🏠 Menú', 'menu')];

const escapar = (texto) =>
  String(texto ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

const horaDe = (iso) =>
  new Intl.DateTimeFormat('es-CO', {
    timeZone: config.zonaHoraria,
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  }).format(new Date(iso));

const icono = (especialidad) => (especialidad === 'barberia' ? '💈' : '💅');

// Muestra un mensaje: edita el anterior si el cliente tocó un botón, o envía uno nuevo.
async function mostrar(ctx, texto, filas = [], { nuevo = false } = {}) {
  const extra = { parse_mode: 'HTML', ...Markup.inlineKeyboard(filas) };

  if (ctx.callbackQuery && !nuevo) {
    try {
      return await ctx.editMessageText(texto, extra);
    } catch (error) {
      if (String(error.description || error.message).includes('message is not modified')) return;
    }
  }

  return ctx.reply(texto, extra);
}

async function responderError(ctx, resultado, reintentar = 'menu') {
  if (resultado.tipo === 'offline') {
    return mostrar(ctx, '⚠️ El sistema de citas no está disponible en este momento. Intenta de nuevo en unos minutos.', [
      [boton('🔄 Reintentar', reintentar)],
      volverAlMenu,
    ]);
  }

  if (resultado.tipo === 'validacion' && resultado.errores?.telegram_id) {
    return pedirContacto(ctx);
  }

  if (resultado.tipo === 'validacion') {
    return mostrar(ctx, `⚠️ ${escapar(resultado.mensaje)}`, [volverAlMenu]);
  }

  return mostrar(ctx, '😕 Algo salió mal. Intenta de nuevo.', [volverAlMenu]);
}

function pedirContacto(ctx) {
  const barberia = config.nombreBarberia ? ` a <b>${escapar(config.nombreBarberia)}</b>` : ' a la barbería';

  return ctx.reply(
    [
      `👋 ¡Hola, ${escapar(ctx.from.first_name)}! Bienvenido${barberia}.`,
      '',
      'Soy el asistente de citas: aquí puedes agendar con el barbero o con la estilista, ver tus citas y cancelarlas.',
      '',
      'Para empezar, toca <b>📱 Compartir mi número</b> abajo. Solo lo usamos para identificar tus citas y avisarte antes de tu turno; no lo compartimos con nadie.',
    ].join('\n'),
    {
      parse_mode: 'HTML',
      ...Markup.keyboard([Markup.button.contactRequest('📱 Compartir mi número')]).oneTime().resize(),
    },
  );
}

function mostrarMenu(ctx, saludo = '¿Qué quieres hacer?') {
  estado.delete(ctx.chat.id);

  return mostrar(ctx, saludo, [
    [boton('📅 Agendar una cita', 'agendar')],
    [boton('📋 Mis citas', 'mis_citas'), boton('❌ Cancelar una cita', 'cancelar')],
    [boton('💈 Servicios y precios', 'servicios'), boton(`🙋 Hablar con ${config.nombreEncargado}`, 'humano')],
  ]);
}

// Funciona aunque la IA no esté disponible: es información fija del negocio.
function mostrarServicios(ctx) {
  return mostrar(ctx, negocio.paraElCliente(escapar), [[boton('📅 Agendar una cita', 'agendar')], volverAlMenu]);
}

// ---- Registro del cliente ----

async function inicio(ctx) {
  const resultado = await api.citasDelCliente(ctx.from.id);

  if (resultado.ok) {
    return mostrarMenu(ctx, `¡Hola de nuevo, ${escapar(ctx.from.first_name)}! ¿Qué quieres hacer?`);
  }

  if (resultado.tipo === 'no_encontrado') {
    return pedirContacto(ctx);
  }

  return responderError(ctx, resultado);
}

async function recibirContacto(ctx) {
  const contacto = ctx.message.contact;

  if (contacto.user_id !== ctx.from.id) {
    return ctx.reply('Por favor comparte tu propio número con el botón "📱 Compartir mi número".');
  }

  const resultado = await api.vincularCliente({
    telegram_id: ctx.from.id,
    telefono: contacto.phone_number,
    nombre: [contacto.first_name, contacto.last_name].filter(Boolean).join(' '),
  });

  if (!resultado.ok) {
    return responderError(ctx, resultado);
  }

  await ctx.reply(`✅ ¡Listo, ${escapar(resultado.data.data.nombre)}! Ya estás registrado.`, {
    parse_mode: 'HTML',
    ...Markup.removeKeyboard(),
  });

  return mostrarMenu(ctx);
}

// ---- Agendar: profesional → día → turno → confirmar ----

async function elegirProfesional(ctx) {
  const resultado = await api.profesionales();

  if (!resultado.ok) return responderError(ctx, resultado, 'agendar');

  const filas = resultado.data.data.map((p) => [
    boton(`${icono(p.especialidad)} ${p.especialidad_texto} · ${p.nombre}`, `prof:${p.id}`),
  ]);

  return mostrar(ctx, '¿Con quién quieres tu cita?', [...filas, volverAlMenu]);
}

async function elegirDia(ctx) {
  const profesionalId = Number(ctx.match[1]);
  const resultado = await api.profesionales();

  if (!resultado.ok) return responderError(ctx, resultado, 'agendar');

  const profesional = resultado.data.data.find((p) => p.id === profesionalId);

  if (!profesional) return mostrar(ctx, 'Ese profesional ya no está disponible.', [volverAlMenu]);

  estado.set(ctx.chat.id, { profesional });

  const dias = proximosDiasHabiles(config.diasParaAgendar);
  const filas = [];

  for (let i = 0; i < dias.length; i += 2) {
    filas.push(dias.slice(i, i + 2).map((d) => boton(d.texto, `dia:${d.iso}:${d.texto}`)));
  }

  return mostrar(ctx, `${icono(profesional.especialidad)} <b>${escapar(profesional.nombre)}</b>\n\n¿Qué día?`, [
    ...filas,
    [boton('↩️ Cambiar profesional', 'agendar')],
  ]);
}

async function elegirTurno(ctx, fechaElegida, fechaTexto, opciones = {}) {
  const seleccion = estado.get(ctx.chat.id);

  if (!seleccion) return seleccionVencida(ctx);

  const fecha = fechaElegida ?? ctx.match[1];
  seleccion.fecha = fecha;
  seleccion.fechaTexto = fechaTexto ?? ctx.match[2];

  const resultado = await api.turnos(seleccion.profesional.id, fecha);

  if (!resultado.ok) return responderError(ctx, resultado, `dia:${fecha}:${seleccion.fechaTexto}`);

  const turnos = resultado.data.data;
  const volver = [boton('↩️ Cambiar día', `prof:${seleccion.profesional.id}`)];

  if (turnos.length === 0) {
    return mostrar(ctx, `😕 No hay turnos libres el día <b>${escapar(seleccion.fechaTexto)}</b>. Elige otro día.`, [volver], opciones);
  }

  const filas = [];

  for (let i = 0; i < turnos.length; i += 3) {
    filas.push(turnos.slice(i, i + 3).map((t) => boton(t.hora, `turno:${t.inicio}`)));
  }

  return mostrar(
    ctx,
    `${icono(seleccion.profesional.especialidad)} <b>${escapar(seleccion.profesional.nombre)}</b> · ${escapar(seleccion.fechaTexto)}\n\nElige tu turno (1 hora):`,
    [...filas, volver],
    opciones,
  );
}

function resumen(ctx) {
  const seleccion = estado.get(ctx.chat.id);

  if (!seleccion?.fecha) return seleccionVencida(ctx);

  seleccion.inicio = ctx.match[1];

  return mostrar(
    ctx,
    [
      '📝 <b>Confirma tu cita</b>',
      '',
      `${icono(seleccion.profesional.especialidad)} ${escapar(seleccion.profesional.nombre)} (${escapar(seleccion.profesional.especialidad_texto)})`,
      `📅 ${escapar(seleccion.fechaTexto)}`,
      `🕐 ${horaDe(seleccion.inicio)}`,
    ].join('\n'),
    [
      [boton('✅ Confirmar', 'confirmar')],
      [boton('↩️ Cambiar hora', `dia:${seleccion.fecha}:${seleccion.fechaTexto}`), boton('❌ Salir', 'menu')],
    ],
  );
}

async function confirmar(ctx) {
  const seleccion = estado.get(ctx.chat.id);

  if (!seleccion?.inicio) return seleccionVencida(ctx);

  const resultado = await api.agendar({
    telegram_id: ctx.from.id,
    profesional_id: seleccion.profesional.id,
    inicio: seleccion.inicio,
  });

  if (resultado.ok) {
    const cita = resultado.data.data;
    estado.delete(ctx.chat.id);
    agente.olvidar(ctx.chat.id);

    return mostrar(
      ctx,
      [
        '✅ <b>¡Cita agendada!</b>',
        '',
        `${icono(cita.profesional.especialidad)} ${escapar(cita.profesional.nombre)}`,
        `📅 ${escapar(cita.fecha_texto)}`,
        `🕐 ${escapar(cita.hora_texto)}`,
        '',
        'Te enviaré un recordatorio antes de la cita.',
      ].join('\n'),
      [volverAlMenu],
    );
  }

  // Si otro cliente tomó el turno mientras confirmaba, se le ofrecen las horas que quedan.
  if (resultado.tipo === 'validacion' && resultado.errores?.inicio) {
    await mostrar(ctx, `⚠️ ${escapar(resultado.mensaje)}`);
    return elegirTurno(ctx, seleccion.fecha, seleccion.fechaTexto, { nuevo: true });
  }

  return responderError(ctx, resultado, 'confirmar');
}

function seleccionVencida(ctx) {
  return mostrar(ctx, '⏱️ Tu selección expiró. Empecemos de nuevo.', [
    [boton('📅 Agendar una cita', 'agendar')],
    volverAlMenu,
  ]);
}

// ---- Mis citas y cancelar ----

function lineaCita(cita) {
  return `${icono(cita.profesional?.especialidad)} <b>${escapar(cita.fecha_texto)}</b> · ${escapar(cita.hora_texto)} con ${escapar(cita.profesional?.nombre)}`;
}

async function misCitas(ctx) {
  const resultado = await api.citasDelCliente(ctx.from.id);

  if (resultado.tipo === 'no_encontrado') return pedirContacto(ctx);
  if (!resultado.ok) return responderError(ctx, resultado, 'mis_citas');

  const citas = resultado.data.data;

  if (citas.length === 0) {
    return mostrar(ctx, 'No tienes citas pendientes.', [[boton('📅 Agendar una cita', 'agendar')], volverAlMenu]);
  }

  return mostrar(ctx, ['📋 <b>Tus próximas citas</b>', '', ...citas.map(lineaCita)].join('\n'), [volverAlMenu]);
}

async function elegirCitaACancelar(ctx) {
  const resultado = await api.citasDelCliente(ctx.from.id);

  if (resultado.tipo === 'no_encontrado') return pedirContacto(ctx);
  if (!resultado.ok) return responderError(ctx, resultado, 'cancelar');

  const citas = resultado.data.data;

  if (citas.length === 0) {
    return mostrar(ctx, 'No tienes citas pendientes para cancelar.', [volverAlMenu]);
  }

  const filas = citas.map((c) => [boton(`${c.fecha_texto} · ${c.hora_texto} · ${c.profesional.nombre}`, `cancelar:${c.id}`)]);

  return mostrar(ctx, '¿Cuál cita quieres cancelar?', [...filas, volverAlMenu]);
}

function confirmarCancelacion(ctx) {
  const citaId = ctx.match[1];

  return mostrar(
    ctx,
    '¿Seguro que quieres cancelar esta cita?\n\n<i>Si faltan menos de 2 horas, la cancelación cuenta como inasistencia. Con 2 inasistencias tu cuenta se bloquea.</i>',
    [[boton('Sí, cancelar', `cancelar_si:${citaId}`), boton('No', 'menu')]],
  );
}

async function cancelar(ctx) {
  const resultado = await api.cancelar(ctx.match[1], ctx.from.id);

  if (resultado.tipo === 'no_encontrado') {
    return mostrar(ctx, 'No encontré esa cita. Puede que ya no exista.', [volverAlMenu]);
  }

  if (!resultado.ok) return responderError(ctx, resultado, `cancelar_si:${ctx.match[1]}`);

  agente.olvidar(ctx.chat.id);

  const simbolo = resultado.data.cuenta_como_inasistencia ? '⚠️' : '✅';

  return mostrar(ctx, `${simbolo} ${escapar(resultado.data.message)}`, [volverAlMenu]);
}

// ---- Atención de una persona ----

async function pasarConPersona(ctx, motivo = '') {
  const { id, first_name: nombre, last_name: apellido, username } = ctx.from;
  const cliente = [nombre, apellido].filter(Boolean).join(' ');
  const enlace = username ? `@${escapar(username)}` : `<a href="tg://user?id=${id}">${escapar(cliente)}</a>`;
  const citas = await api.citasDelCliente(id);
  const telefono = citas.ok ? citas.data.cliente?.telefono : null;

  const aviso = [
    '🙋 <b>Un cliente quiere hablar contigo</b>',
    '',
    `${escapar(cliente)} (${enlace})`,
    telefono ? `📱 ${escapar(telefono)}` : null,
    motivo ? `💬 ${escapar(motivo)}` : null,
  ].filter((linea) => linea !== null).join('\n');

  let avisados = 0;

  for (const destino of config.avisosTelegramIds) {
    try {
      await ctx.telegram.sendMessage(destino, aviso, { parse_mode: 'HTML' });
      avisados++;
    } catch (error) {
      console.error(`[humano] No se pudo avisar a ${destino}: ${error.message}`);
    }
  }

  return avisados;
}

async function pedirPersona(ctx) {
  const avisados = await pasarConPersona(ctx, 'Tocó el botón para hablar con una persona.');

  if (avisados === 0) {
    return mostrar(ctx, `😕 No pude avisarle a ${escapar(config.nombreEncargado)} en este momento. Intenta de nuevo más tarde.`, [volverAlMenu]);
  }

  return mostrar(ctx, `🙋 Listo, le avisé a <b>${escapar(config.nombreEncargado)}</b>. Te escribe por aquí en cuanto pueda.`, [volverAlMenu]);
}

// ---- Conversación con IA ----

// Lo que la IA puede mostrarle al cliente. Crear o cancelar siempre requiere que él toque el botón.
const accionesIa = {
  async mostrarConfirmacion(ctx, profesional, fecha, inicio) {
    const fechaTexto = new Intl.DateTimeFormat('es-CO', {
      timeZone: config.zonaHoraria, weekday: 'long', day: 'numeric', month: 'long',
    }).format(new Date(inicio));

    estado.set(ctx.chat.id, { profesional, fecha, fechaTexto, inicio });

    return ctx.reply(
      [
        '📝 <b>Confirma tu cita</b>',
        '',
        `${icono(profesional.especialidad)} ${escapar(profesional.nombre)} (${escapar(profesional.especialidad_texto)})`,
        `📅 ${escapar(fechaTexto)}`,
        `🕐 ${horaDe(inicio)}`,
      ].join('\n'),
      { parse_mode: 'HTML', ...Markup.inlineKeyboard([[boton('✅ Confirmar', 'confirmar'), boton('❌ Salir', 'menu')]]) },
    );
  },
  mostrarCancelacion(ctx, citaId) {
    return ctx.reply('¿Cancelo esta cita?', Markup.inlineKeyboard([[boton('Sí, cancelar', `cancelar_si:${citaId}`), boton('No', 'menu')]]));
  },
};

async function conversar(ctx) {
  const registrado = await api.citasDelCliente(ctx.from.id);

  if (registrado.tipo === 'no_encontrado') return pedirContacto(ctx);

  if (agente.pidePersona(ctx.message.text)) return pedirPersona(ctx);

  const citaSinConfirmar = Boolean(estado.get(ctx.chat.id)?.inicio);

  if (citaSinConfirmar && agente.confirmaPorTexto(ctx.message.text)) return confirmar(ctx);

  if (agente.preguntaPorSuCita(ctx.message.text)) {
    if (!citaSinConfirmar) return misCitas(ctx);

    return ctx.reply('Todavía no está agendada: falta confirmarla. Toca Confirmar o respóndeme "sí".', Markup.inlineKeyboard([
      [boton('✅ Confirmar', 'confirmar'), boton('❌ Salir', 'menu')],
    ]));
  }

  await ctx.sendChatAction('typing').catch(() => {});

  const respuesta = await agente.responder(ctx, ctx.message.text, accionesIa, { citaSinConfirmar });

  if (respuesta === agente.YA_RESPONDIO) return undefined;
  if (!respuesta) return mostrarMenu(ctx, 'En este momento te atiendo con los botones 👇');

  // El botón aparece solo cuando hace falta; siempre sigue disponible en el menú.
  if (!agente.ofrecerPersona(ctx.message.text, respuesta)) return ctx.reply(respuesta);

  return ctx.reply(respuesta, Markup.inlineKeyboard([[boton(`🙋 Hablar con ${config.nombreEncargado}`, 'humano')]]));
}

// ---- Registro de manejadores ----

function registrarFlujo(bot) {
  // Toda acción de un botón responde el "callback" para que Telegram quite el reloj de carga.
  bot.on('callback_query', async (ctx, next) => {
    await ctx.answerCbQuery().catch(() => {});
    return next();
  });

  bot.start(inicio);
  bot.command('menu', (ctx) => mostrarMenu(ctx));
  bot.command('agendar', elegirProfesional);
  bot.command('citas', misCitas);
  // Para configurar AVISOS_TELEGRAM_IDS: quien deba recibir avisos escribe /miid.
  bot.command('miid', (ctx) => ctx.reply(`Tu ID de Telegram es: ${ctx.from.id}`));
  bot.on('contact', recibirContacto);

  bot.action('menu', (ctx) => mostrarMenu(ctx));
  bot.action('agendar', elegirProfesional);
  bot.action(/^prof:(\d+)$/, elegirDia);
  bot.action(/^dia:(\d{4}-\d{2}-\d{2}):(.+)$/, (ctx) => elegirTurno(ctx));
  bot.action(/^turno:(.+)$/, resumen);
  bot.action('confirmar', confirmar);
  bot.action('mis_citas', misCitas);
  bot.action('cancelar', elegirCitaACancelar);
  bot.action('humano', pedirPersona);
  bot.action('servicios', mostrarServicios);
  bot.action(/^cancelar:(\d+)$/, confirmarCancelacion);
  bot.action(/^cancelar_si:(\d+)$/, cancelar);
  bot.action(/^asisto:(\d+)$/, (ctx) => mostrar(ctx, '🙌 ¡Gracias por confirmar! Te esperamos.'));

  // Texto libre: con IA activa se conversa; sin ella, se muestra el menú.
  bot.on('text', (ctx) => (config.iaProveedor ? conversar(ctx) : mostrarMenu(ctx, 'No entendí ese mensaje 🙈. Usa los botones:')));
}

module.exports = { registrarFlujo, horaDe, escapar };
