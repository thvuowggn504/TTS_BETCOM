import 'laravel-vite-plugin/js/bootstrap';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';