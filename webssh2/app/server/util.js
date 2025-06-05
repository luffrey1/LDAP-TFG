/* jshint esversion: 6, asi: true, node: true */
// util.js

// private
const debug = require('debug')('WebSSH2');
const Auth = require('basic-auth');
const config = require('./config');

let defaultCredentials = { username: null, password: null, privatekey: null };

function setDefaultCredentials(user) {
  if (!user) return;
  
  if (user.name) {
    config.ssh.username = user.name;
  }
  if (user.password) {
    config.ssh.password = user.password;
  }
  if (user.privatekey) {
    config.ssh.privateKey = user.privatekey;
  }
}

function basicAuth(req, res, next) {
  // Si no hay configuración de usuario, permitir acceso
  if (!config.user || (!config.user.name && !config.user.password && !config.user.privatekey)) {
    return next();
  }

  // Si overridebasic es true, permitir acceso
  if (config.user.overridebasic) {
    return next();
  }

  // Verificar autenticación básica
  const auth = req.headers.authorization;
  if (!auth) {
    res.setHeader('WWW-Authenticate', 'Basic realm="WebSSH2"');
    return res.status(401).send('Authentication required');
  }

  const [type, credentials] = auth.split(' ');
  if (type !== 'Basic') {
    return res.status(401).send('Invalid authentication type');
  }

  const [username, password] = Buffer.from(credentials, 'base64').toString().split(':');
  
  // Verificar credenciales
  if (username === config.user.name && password === config.user.password) {
    // Establecer las credenciales en la sesión
    if (req.session) {
      req.session.username = username;
      req.session.userpassword = password;
    }
    return next();
  }

  res.setHeader('WWW-Authenticate', 'Basic realm="WebSSH2"');
  return res.status(401).send('Invalid credentials');
}

// takes a string, makes it boolean (true if the string is true, false otherwise)
exports.parseBool = function parseBool(str) {
  return str.toLowerCase() === 'true';
};

module.exports = {
  setDefaultCredentials,
  basicAuth
};
