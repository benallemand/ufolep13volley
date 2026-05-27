// Import du layout pour les pages team leader
import AppLayout from './components/layout/AppLayout.js';

// Montage de l'application Vue
const app = Vue.createApp(AppLayout);
app.use(AppLayout.router);
app.mount('#app');