// Configurazione principale di Vue.js
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import axios from 'axios';

// Import Bootstrap and Bootstrap Icons
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import * as bootstrap from 'bootstrap';
import './assets/css/style.css';

// Make Bootstrap available globally
window.bootstrap = bootstrap;

// Configurazione di Axios
axios.defaults.baseURL = 'http://localhost/GymFinder_Vue_PHP/backend/api';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

axios.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Gestione degli errori globali di Axios
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      // Token scaduto o non valido
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      router.push('/login');
    }
    return Promise.reject(error);
  }
);

// Creazione dell'app Vue
const app = createApp(App);

// Registrazione dei componenti globali
app.use(createPinia());
app.use(router);

// Montaggio dell'app
app.mount('#app');
