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
    methods: ["GET", "POST"]
  }
});

// Middleware para debug
app.use((req, res, next) => {
  console.log('Request path:', req.path);
  console.log('Session ID:', req.sessionID);
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
app.post('/ssh/host/:host?', connect);
app.post('/ssh', express.static(publicPath, config.express.ssh));
app.use('/ssh', express.static(publicPath, config.express.ssh));
app.use(basicAuth);
app.get('/ssh/reauth', reauth);
app.get('/ssh/host/:host?', connect);
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
io.on('connection', appSocket);

// socket.io middleware
io.use((socket, next) => {
  if (socket.request.res) {
    session(socket.request, socket.request.res, (err) => {
      if (err) {
        console.error('Session middleware error:', err);
        return next(new Error('Session error'));
      }
      console.log('Socket session:', {
        id: socket.request.session?.id,
        cookie: socket.request.session?.cookie
      });
      next();
    });
  } else {
    next();
  }
});

// Manejar errores de Socket.IO
io.on('connect_error', (err) => {
  console.error('Socket.IO connection error:', err);
});

io.on('connect_timeout', (timeout) => {
  console.error('Socket.IO connection timeout:', timeout);
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
