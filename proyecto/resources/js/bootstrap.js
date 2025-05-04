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
