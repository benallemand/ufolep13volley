// Import du layout pour les pages team leader
import TeamLeaderLayout from './components/layout/TeamLeaderLayout.js';

// Montage de l'application Vue
const app = Vue.createApp(TeamLeaderLayout);
app.use(TeamLeaderLayout.router);
app.mount('#app');