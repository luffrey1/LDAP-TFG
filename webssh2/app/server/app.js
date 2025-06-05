/* jshint esversion: 6, asi: true, node: true */
/* eslint no-unused-expressions: ["error", { "allowShortCircuit": true, "allowTernary": true }],
   no-console: ["error", { allow: ["warn", "error", "info"] }] */
// app.js

// eslint-disable-next-line import/order
const config = require('./config');
const path = require('path');

const nodeRoot = path.dirname(require.main.filename);
const publicPath = path.join(nodeRoot, 'client', 'public');
const express = require('express');
const logger = require('morgan');

const app = express();
const server = require('http').createServer(app);
const favicon = require('serve-favicon');

// Función de logging mejorada
function log(message, data = null) {
  const timestamp = new Date().toISOString();
  console.log('\n=== WebSSH2 Log ===');
  console.log(`[${timestamp}] ${message}`);
  if (data) {
    console.log('Data:', JSON.stringify(data, null, 2));
  }
  console.log('==================\n');
}

// Log inicial
log('Iniciando WebSSH2 Server', {
  config: {
    port: config.listen.port,
    ip: config.listen.ip,
    socketPath: config.socketio.path
  }
});

// Configurar sesión
const session = require('express-session')({
  secret: 'mysecret',
  name: 'WebSSH2',
  resave: true,
  saveUninitialized: true,
  cookie: {
    path: '/ssh',
    httpOnly: true,
    secure: false,
    maxAge: 86400000 // 24 horas
  }
});

// Usar sesión
app.use(session);

// Configurar Socket.IO
const io = require('socket.io')(server, {
  path: '/ssh/socket.io',
  transports: ['websocket', 'polling'],
  allowEIO3: true,
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    credentials: true
  },
  cookie: {
    name: 'WebSSH2',
    path: '/ssh',
    httpOnly: true,
    secure: false
  }
});

// Middleware para debug
app.use((req, res, next) => {
  log('HTTP Request', {
    path: req.path,
    method: req.method,
    sessionID: req.sessionID,
    cookies: req.headers.cookie,
    headers: req.headers
  });
  next();
});

// Configurar CORS
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
  res.header('Access-Control-Allow-Credentials', 'true');
  next();
});

const appSocket = require('./socket');
const { setDefaultCredentials, basicAuth } = require('./util');
const { webssh2debug } = require('./logging');
const { reauth, connect, notfound, handleErrors } = require('./routes');

setDefaultCredentials(config.user);

// safe shutdown
let remainingSeconds = config.safeShutdownDuration;
let shutdownMode = false;
let shutdownInterval;
let connectionCount = 0;
// eslint-disable-next-line consistent-return
function safeShutdownGuard(req, res, next) {
  if (!shutdownMode) return next();
  res.status(503).end('Service unavailable: Server shutting down');
}
// express
app.use(safeShutdownGuard);
if (config.accesslog) app.use(logger('common'));
app.disable('x-powered-by');
app.use(favicon(path.join(publicPath, 'favicon.ico')));
app.use(express.urlencoded({ extended: true }));

// Rutas
app.post('/ssh/host/:host?', (req, res) => {
  log('POST /ssh/host', { host: req.params.host, body: req.body });
  connect(req, res);
});

app.get('/ssh/host/:host?', (req, res) => {
  log('GET /ssh/host', { host: req.params.host, query: req.query });
  connect(req, res);
});

app.post('/ssh', express.static(path.join(__dirname, 'client', 'public')));
app.use('/ssh', express.static(path.join(__dirname, 'client', 'public')));
app.use(basicAuth);
app.get('/ssh/reauth', reauth);
app.use(notfound);
app.use(handleErrors);

// clean stop
function stopApp(reason) {
  shutdownMode = false;
  if (reason) console.info(`Stopping: ${reason}`);
  clearInterval(shutdownInterval);
  io.close();
  server.close();
}

// bring up socket
io.on('connection', (socket) => {
  log('New Socket.IO Connection', {
    id: socket.id,
    transport: socket.conn.transport.name,
    handshake: socket.handshake
  });
  appSocket(socket);
});

// socket.io middleware
io.use((socket, next) => {
  // Si no hay sesión, permitir la conexión
  if (!socket.request.session) {
    return next();
  }

  // Si overridebasic es true, permitir la conexión
  if (config.user && config.user.overridebasic) {
    return next();
  }

  // Verificar autenticación
  const auth = socket.request.headers.authorization;
  if (!auth) {
    return next(new Error('Authentication required'));
  }

  const [type, credentials] = auth.split(' ');
  if (type !== 'Basic') {
    return next(new Error('Invalid authentication type'));
  }

  const [username, password] = Buffer.from(credentials, 'base64').toString().split(':');
  
  // Verificar credenciales
  if (username === config.user.name && password === config.user.password) {
    return next();
  }

  return next(new Error('Invalid credentials'));
});

// Manejar errores de Socket.IO
io.on('connect_error', (err) => {
  log('Socket.IO Connection Error', { error: err.message });
});

io.on('error', (err) => {
  log('Socket.IO Error', { error: err.message });
});

function countdownTimer() {
  if (!shutdownMode) clearInterval(shutdownInterval);
  remainingSeconds -= 1;
  if (remainingSeconds <= 0) {
    stopApp('Countdown is over');
  } else io.emit('shutdownCountdownUpdate', remainingSeconds);
}

const signals = ['SIGTERM', 'SIGINT'];
signals.forEach((signal) =>
  process.on(signal, () => {
    if (shutdownMode) stopApp('Safe shutdown aborted, force quitting');
    if (!connectionCount > 0) stopApp('All connections ended');
    shutdownMode = true;
    console.error(
      `\r\n${connectionCount} client(s) are still connected.\r\nStarting a ${remainingSeconds} seconds countdown.\r\nPress Ctrl+C again to force quit`
    );
    if (!shutdownInterval) shutdownInterval = setInterval(countdownTimer, 1000);
  })
);

module.exports = { server, config };

const onConnection = (socket) => {
  connectionCount += 1;
  socket.on('disconnect', () => {
    connectionCount -= 1;
    if (connectionCount <= 0 && shutdownMode) {
      stopApp('All clients disconnected');
    }
  });
  socket.on('geometry', (cols, rows) => {
    // TODO need to rework how we pass settings to ssh2, this is less than ideal
    socket.request.session.ssh.cols = cols;
    socket.request.session.ssh.rows = rows;
    webssh2debug(socket, `SOCKET GEOMETRY: termCols = ${cols}, termRows = ${rows}`);
  });
};

io.on('connection', onConnection);

// Log cuando el servidor inicia
server.listen(config.listen.port, config.listen.ip, () => {
  log('Server Started', {
    port: config.listen.port,
    ip: config.listen.ip,
    config: {
      socketPath: config.socketio.path,
      transports: config.socketio.transports
    }
  });
});
