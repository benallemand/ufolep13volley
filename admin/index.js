// Point d'entrée de l'administration Vue (issue #265, lot 0).
// Remplace progressivement `admin.php` + `js/administration.js` (ExtJS).
import { createApp } from 'vue';
import axios from 'axios';
import Toastify from 'toastify-js';
import AdminLayout from './components/layout/AdminLayout.js';

// Exposés en global : les sous-composants les utilisent sans import, comme sur
// le reste du site.
window.axios = axios;
window.Toastify = Toastify;

const app = createApp(AdminLayout);
app.use(AdminLayout.router);
app.mount('#admin-app');
