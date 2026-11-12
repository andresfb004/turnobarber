# TurnoBarber

Sistema de citas para una barbería con dos profesionales: el barbero y la estilista. Los clientes agendan turnos de 1 hora por un bot de Telegram, y el equipo administra las dos agendas desde un panel web.

Proyecto de Aula · Desarrollo y Arquitectura Backend · UNAB.

## Estructura

```text
proyecto_aula/
├── backend/   # Laravel: API (Sanctum) y panel web
├── cliente/   # Bot de Telegram (Node.js), fuera de Laravel
├── docs/      # Ficha y documento de arquitectura C4
└── postman/   # Colección de la API
```

## Reglas de negocio

| Regla | Qué hace |
| --- | --- |
| RN1 · Turnos disponibles | Turnos de 1 h en punto dentro del horario de cada profesional (7:00–12:00 y 2:00–7:00 p. m., lunes a sábado), sin el descanso de mediodía, cierres ni citas tomadas |
| RN2 · No cruce por profesional | Un profesional no puede tener dos citas en el mismo turno; profesionales distintos sí pueden atender a la misma hora |
| RN3 · Anticipación | Se agenda desde 1 h y hasta 14 días adelante; cancelar con menos de 2 h cuenta como inasistencia |
| RN4 · Bloqueo | Con 2 inasistencias el cliente queda bloqueado 14 días; luego se desbloquea solo y empieza de cero. El administrador puede desbloquearlo antes |

Datos que da el bot (de la entrevista con el barbero, en `cliente/src/negocio.js`): corte de caballero $45.000 con barba incluida; servicios de la estilista con precios aproximados que ella confirma; pago en efectivo o transferencia (sin tarjeta); niños solo con la estilista; 25 minutos de tolerancia si el cliente llega tarde.

## Correr el backend en local

Requiere PHP 8.4 o superior y Composer.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

La API queda en `http://localhost:8000/api`. En local se usa SQLite; en producción, MySQL.

Usuarios de prueba que crea el seeder (contraseña `password`):

| Correo | Rol |
| --- | --- |
| `barbero@turnobarber.test` | Administrador |
| `estilista@turnobarber.test` | Administrador |
| `bot@turnobarber.test` | Bot |

Pruebas automáticas:

```bash
php artisan test
```

## Panel web

Blade con Bootstrap 5 (desde CDN, sin compilar nada). Se entra en `/login`.

| Rol | Qué puede hacer |
| --- | --- |
| Administrador | Dashboard, las dos agendas (juntas o por profesional), crear citas, CRUD de profesionales, cierres y clientes, desbloquear clientes |
| Profesional | Solo su agenda: ver sus citas y marcar si el cliente asistió |

- **Dashboard:** citas de hoy por profesional, ocupación de la semana, inasistencias del mes, clientes bloqueados y gráfico de citas atendidas por día (Chart.js).
- **Agenda:** una columna por profesional y una fila por turno. Desde cada cita se marca atendida o no asistió (suma una inasistencia, RN4), o se cancela si aún no empieza.
- **CRUD con búsqueda y paginación:** profesionales (con su cuenta y horario), cierres (festivos o diligencias, a quién afectan) y clientes (con el historial de citas que llegan del bot).
- Páginas de error propias: 403, 404, 419, 500 y 503.

## API

Todas las rutas, menos `login`, requieren el token Sanctum en la cabecera `Authorization: Bearer <token>`.

| Método | Ruta | Uso |
| --- | --- | --- |
| POST | `/api/login` | Inicia sesión y devuelve el token |
| POST | `/api/logout` | Invalida el token |
| POST | `/api/clientes/vincular` | Registra al cliente por su `telegram_id` y teléfono |
| GET | `/api/profesionales` | Profesionales activos |
| GET | `/api/profesionales/{id}` | Detalle con su horario |
| POST | `/api/profesionales` | Crea un profesional con su cuenta y horario (solo administrador) |
| PUT | `/api/profesionales/{id}` | Edita un profesional (solo administrador) |
| DELETE | `/api/profesionales/{id}` | Elimina un profesional sin citas, 204 (solo administrador) |
| GET | `/api/turnos?profesional={id}&fecha={Y-m-d}` | Turnos libres (RN1) |
| POST | `/api/citas` | Agenda un turno (RN2, RN3, RN4) |
| GET | `/api/citas/{id}` | Detalle de una cita |
| PUT | `/api/citas/{id}` | Reprograma la cita a otro turno (RN2, RN3, RN4) |
| DELETE | `/api/citas/{id}` | Borra una cita registrada por error, 204 (solo administrador) |
| GET | `/api/clientes/{telegram_id}/citas` | Próximas citas del cliente |
| PATCH | `/api/citas/{id}/cancelar` | Cancela una cita (RN3) |
| GET | `/api/recordatorios/pendientes` | Citas de las próximas 2 h sin aviso |
| PATCH | `/api/recordatorios/{id}/enviado` | Marca el aviso como enviado |

CRUD completo (GET, POST, PUT, DELETE) para **profesionales** y **citas**, con API Resources. Códigos: 200, 201, 204, 401, 403, 404 y 422.

La colección `postman/TurnoBarber.postman_collection.json` trae el flujo del bot, un ejemplo de cada error y el CRUD de administración. Por defecto apunta a `http://localhost:8000/api` con los datos del seeder. Para probar en producción importa también `postman/TurnoBarber-produccion.postman_environment.json`, selecciónalo y escribe ahí `bot_password` y `admin_password` (no se guardan en el repositorio).

## Bot de Telegram (`cliente/`)

Cliente externo en Node.js con Telegraf. Vive fuera de Laravel y solo habla con la API: inicia sesión con el usuario del bot, guarda el token y lo usa en cada petición. El cliente elige todo con botones: con quién, qué día y qué turno.

| Situación | Qué hace el bot |
| --- | --- |
| La API no responde | Avisa que el sistema no está disponible y ofrece reintentar, sin caerse |
| 401 (token vencido) | Vuelve a iniciar sesión y repite la petición una vez |
| 422 (regla rechazada) | Muestra el mensaje de la API; si el turno se tomó, ofrece las horas que quedan |

Cada 10 minutos consulta los recordatorios pendientes y le escribe a cada cliente con botones para confirmar o cancelar.

### Correr el bot

Requiere Node.js 20.12 o superior y un bot creado con [@BotFather](https://t.me/BotFather).

```bash
cd cliente
npm install
cp .env.example .env   # pon BOT_TOKEN y BOT_PASSWORD
npm start
```

Usa modo *polling*: no necesita URL pública, así que puede correr en un portátil.

### Bot encendido 24/7 en Railway

En el mismo proyecto de Railway se agrega un segundo servicio desde el mismo repositorio:

1. **New → GitHub Repo → turnobarber**.
2. **Settings → Root Directory** = `cliente`. No se genera dominio: el bot no recibe peticiones HTTP.
3. **Variables**:

    ```text
    BOT_TOKEN=...
    API_URL=https://davidorozcobarberia.up.railway.app/api
    BOT_EMAIL=bot@turnobarber.test
    BOT_PASSWORD=...
    ZONA_HORARIA=America/Bogota
    BARBERIA_NOMBRE=Nombre de la barbería
    ```

Telegram solo permite una copia del bot recibiendo mensajes a la vez: si el bot corre en Railway, no lo inicies también en local.

### Pruebas del bot

Simulan a Telegram y recorren la conversación contra una API local con los datos del seeder:

```bash
cd backend && php artisan migrate:fresh --seed && php artisan serve --port=8001
# en otra terminal
cd cliente && npm test
```

## Despliegue en Railway

1. **New Project → Deploy from GitHub repo** y elige este repositorio.
2. En el servicio: **Settings → Root Directory** = `backend`.
3. En el mismo proyecto: **New → Database → MySQL**.
4. En el servicio de Laravel: **Variables → Raw Editor** y pega:

    ```text
    APP_NAME=TurnoBarber
    APP_ENV=production
    APP_DEBUG=false
    APP_KEY=base64:...
    APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
    APP_TIMEZONE=America/Bogota
    APP_LOCALE=es
    APP_FALLBACK_LOCALE=en
    APP_FAKER_LOCALE=es_ES
    LOG_CHANNEL=stderr
    LOG_LEVEL=info
    DB_CONNECTION=mysql
    DB_URL=${{MySQL.MYSQL_URL}}
    SESSION_DRIVER=database
    CACHE_STORE=database
    QUEUE_CONNECTION=database
    SEED_ADMIN_PASSWORD=...
    SEED_BOT_EMAIL=bot@turnobarber.test
    SEED_BOT_PASSWORD=...
    ```

    Genera el `APP_KEY` en local con `php artisan key:generate --show`. Usa contraseñas propias en `SEED_ADMIN_PASSWORD` y `SEED_BOT_PASSWORD`, no `password`.

5. **Settings → Networking → Generate Domain** para tener la URL pública con HTTPS.
6. Las migraciones corren solas en cada despliegue (`railway.json`). Los datos de demostración se cargan una sola vez desde la consola del servicio:

    ```bash
    php artisan db:seed --force
    ```

7. Comprueba que `https://<tu-dominio>/api/profesionales` responda 401 sin token.

El archivo `.env` nunca se sube al repositorio; `backend/.env.example` tiene la lista de variables.
