// Import du layout pour les pages team leader
import { createApp } from 'vue';
import axios from 'axios';
import Toastify from 'toastify-js';
import { Notyf } from 'notyf';
import AppLayout from './components/layout/AppLayout.js';

// Expose les libs en global pour les sous-composants qui les utilisent sans import
window.axios = axios;
window.Toastify = Toastify;
window.Notyf = Notyf;

// Montage de l'application Vue
const app = createApp(AppLayout);
app.use(AppLayout.router);
app.mount('#app');