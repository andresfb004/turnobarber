// Información del negocio que el cliente puede preguntar. Viene de la entrevista con el barbero.
// Los precios de la estilista son rangos aproximados del mercado en Bucaramanga: él no dio
// precios fijos, así que el bot siempre aclara que ella confirma el valor exacto.

const horario = 'Lunes a sábado de 7:00 a. m. a 12:00 m. y de 2:00 p. m. a 7:00 p. m. Domingos cerrado.';
const pagos = 'Efectivo o transferencia. No recibimos tarjeta.';
const toleranciaMinutos = 25;

const servicios = {
  barberia: [
    { nombre: 'Corte de caballero (incluye barba)', precio: '$45.000' },
  ],
  belleza: [
    { nombre: 'Corte de dama', precio: '$25.000 a $45.000' },
    { nombre: 'Corte para niños y niñas', precio: '$15.000 a $25.000' },
    { nombre: 'Cepillado', precio: '$25.000 a $40.000' },
    { nombre: 'Tinte', precio: '$80.000 a $150.000' },
    { nombre: 'Mechas o balayage', precio: '$150.000 a $300.000' },
    { nombre: 'Keratina o alisado', precio: '$120.000 a $250.000' },
    { nombre: 'Manicure', precio: '$18.000 a $25.000' },
    { nombre: 'Pedicure', precio: '$22.000 a $30.000' },
    { nombre: 'Uñas semipermanentes', precio: '$35.000 a $50.000' },
    { nombre: 'Uñas acrílicas', precio: '$70.000 a $110.000' },
    { nombre: 'Diseño de cejas', precio: '$15.000 a $25.000' },
    { nombre: 'Maquillaje social', precio: '$60.000 a $100.000' },
  ],
};

const notas = [
  'Los turnos duran 1 hora.',
  `Si llegas tarde, te esperamos máximo ${toleranciaMinutos} minutos.`,
  'A niños y niñas los atiende solo la estilista.',
  'Con 2 inasistencias sin avisar, la cuenta queda bloqueada 14 días.',
];

const lista = (items) => items.map((s) => `- ${s.nombre}: ${s.precio}`).join('\n');

/** Texto para las instrucciones del modelo. */
function paraLaIa() {
  return `Datos del negocio (responde con ellos; si algo no está aquí, ofrece hablar con la persona encargada):
- Horario: ${horario}
- Pagos: ${pagos}
- ${notas.join('\n- ')}
Barbería (el barbero):
${lista(servicios.barberia)}
Belleza (la estilista), precios APROXIMADOS que ella confirma según el servicio:
${lista(servicios.belleza)}`;
}

/** Mensaje del botón "Servicios y precios" (HTML de Telegram). */
function paraElCliente(escapar) {
  const bloque = (items) => items.map((s) => `• ${escapar(s.nombre)}: <b>${escapar(s.precio)}</b>`).join('\n');

  return [
    '💈 <b>Barbería</b>',
    bloque(servicios.barberia),
    '',
    '💅 <b>Belleza</b> (precios aproximados; la estilista te confirma el valor exacto)',
    bloque(servicios.belleza),
    '',
    `🕐 ${escapar(horario)}`,
    `💵 ${escapar(pagos)}`,
    ...notas.map((n) => `• ${escapar(n)}`),
  ].join('\n');
}

module.exports = { horario, pagos, toleranciaMinutos, servicios, paraLaIa, paraElCliente };
