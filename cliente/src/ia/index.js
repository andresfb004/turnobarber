// Elige el proveedor de IA según IA_PROVEEDOR. Vacío: el bot funciona solo con botones.
const config = require('../config');

const proveedores = {
  ollama: () => require('./ollama'),
};

function proveedorActivo() {
  const crear = proveedores[config.iaProveedor];
  return crear ? crear() : null;
}

module.exports = { proveedorActivo };
