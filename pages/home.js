// Import du layout pour les pages team leader
import AppLayout from './components/layout/AppLayout.js';

// Montage de l'application Vue
new Vue({
    el: '#app',
    render: h => h(AppLayout)
});