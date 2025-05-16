// Importar axios de manera tradicional en lugar de usar import
// import axios from 'axios';

// Verificar si axios ya está disponible globalmente
if (typeof axios === 'undefined') {
    console.warn('Axios no está disponible. Algunas funciones de red podrían no funcionar.');
    // Crear un objeto vacío para evitar errores
    window.axios = {
        defaults: {
            headers: {
                common: {}
            }
        }
    };
} else {
    window.axios = axios;
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

// Configuración de Laravel Reverb
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});
