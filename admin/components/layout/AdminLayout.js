import { defineAsyncComponent } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import { requireRoles } from '../../../pages/components/auth/guard.js';

/**
 * Shell de l'administration Vue (issue #265, lot 0).
 *
 * Remplace le `Ext.container.Viewport` + `tabpanel` de `js/administration.js`.
 * Les écrans sont des routes chargées à la demande ; la navigation est une
 * barre latérale repliable, utilisable sur mobile — ce que l'admin ExtJS
 * n'était pas.
 *
 * Le menu ne liste que les écrans déjà migrés. Les autres restent accessibles
 * dans l'admin ExtJS via le lien « ancienne administration », jusqu'à la fin de
 * la migration.
 */

const routes = [
    { path: '/gymnasiums', component: () => import('../screens/Gymnasiums.js') },
    { path: '/:pathMatch(.*)*', redirect: '/gymnasiums' },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

/** Écrans migrés, dans l'ordre du menu. */
export const MENU = [
    { path: '/gymnasiums', label: 'Gymnases', icon: 'fas fa-building' },
];

export default {
    components: {
        'admin-sidebar': defineAsyncComponent(() => import('./AdminSidebar.js')),
    },
    router,
    template: `
      <div v-if="checking" class="flex items-center justify-center min-h-screen gap-3">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <span>Vérification des droits…</span>
      </div>

      <div v-else-if="user" class="drawer lg:drawer-open">
        <input id="admin-drawer" type="checkbox" class="drawer-toggle"/>

        <div class="drawer-content flex flex-col min-h-screen">
          <div class="navbar bg-base-200 lg:hidden">
            <label for="admin-drawer" class="btn btn-square btn-ghost">
              <i class="fas fa-bars"></i>
            </label>
            <span class="font-bold ml-2">Administration</span>
          </div>
          <main class="flex-1">
            <router-view></router-view>
          </main>
        </div>

        <div class="drawer-side z-40">
          <label for="admin-drawer" class="drawer-overlay"></label>
          <admin-sidebar :user="user"></admin-sidebar>
        </div>
      </div>
    `,
    data() {
        return {
            user: null,
            checking: true,
        };
    },
    async created() {
        // Garde d'UX : le vrai contrôle est côté backend, où chaque endpoint
        // d'administration est déclaré 'admin' dans rest/access.php (issue #270).
        this.user = await requireRoles(['admin']);
        this.checking = false;
    },
};
