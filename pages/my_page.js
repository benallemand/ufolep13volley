// Import du layout pour les pages team leader
import { createApp } from 'vue';
import axios from 'axios';
import Toastify from 'toastify-js';
import { Notyf } from 'notyf';
import TeamLeaderLayout from './components/layout/TeamLeaderLayout.js';

window.axios = axios;
window.Toastify = Toastify;
window.Notyf = Notyf;

// Montage de l'application Vue
const app = createApp(TeamLeaderLayout);
app.use(TeamLeaderLayout.router);
app.mount('#app');