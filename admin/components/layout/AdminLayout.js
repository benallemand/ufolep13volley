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
    { path: '/users', component: () => import('../screens/Users.js') },
    { path: '/clubs', component: () => import('../screens/Clubs.js') },
    { path: '/teams', component: () => import('../screens/Teams.js') },
    { path: '/players', component: () => import('../screens/Players.js') },
    { path: '/gymnasiums', component: () => import('../screens/Gymnasiums.js') },
    { path: '/competitions', component: () => import('../screens/Competitions.js') },
    { path: '/days', component: () => import('../screens/Days.js') },
    { path: '/ranks', component: () => import('../screens/Ranks.js') },
    { path: '/limit-dates', component: () => import('../screens/LimitDates.js') },
    { path: '/week-schedule', component: () => import('../screens/WeekSchedule.js') },
    { path: '/:pathMatch(.*)*', redirect: '/users' },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

/** Écrans migrés, dans l'ordre du menu. */
export const MENU = [
    { path: '/users', label: 'Utilisateurs', icon: 'fas fa-users' },
    { path: '/clubs', label: 'Clubs', icon: 'fas fa-sitemap' },
    { path: '/teams', label: 'Équipes', icon: 'fas fa-people-group' },
    { path: '/players', label: 'Joueurs', icon: 'fas fa-person-running' },
    { path: '/gymnasiums', label: 'Gymnases', icon: 'fas fa-building' },
    { path: '/competitions', label: 'Compétitions', icon: 'fas fa-trophy' },
    { path: '/days', label: 'Journées', icon: 'fas fa-calendar-day' },
    { path: '/ranks', label: 'Divisions / poules', icon: 'fas fa-list-ol' },
    { path: '/limit-dates', label: 'Dates limites', icon: 'fas fa-hourglass-end' },
    { path: '/week-schedule', label: 'Planning semaine', icon: 'fas fa-calendar-week' },
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
