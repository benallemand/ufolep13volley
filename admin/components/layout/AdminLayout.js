import { defineAsyncComponent } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import { requireRoles } from '../../../pages/components/auth/guard.js';

/**
 * Shell de l'administration Vue (issue #265).
 *
 * Remplace le `Ext.container.Viewport` + `tabpanel` de `js/administration.js`,
 * supprimé au lot 6 avec le reste d'ExtJS. Les écrans sont des routes chargées
 * à la demande ; la navigation est une barre latérale repliable, utilisable sur
 * mobile — ce que l'admin ExtJS n'était pas.
 */

const routes = [
    { path: '/users', component: () => import('../screens/Users.js') },
    { path: '/clubs', component: () => import('../screens/Clubs.js') },
    { path: '/teams', component: () => import('../screens/Teams.js') },
    { path: '/players', component: () => import('../screens/Players.js') },
    { path: '/gymnasiums', component: () => import('../screens/Gymnasiums.js') },
    { path: '/matches', component: () => import('../screens/Matches.js') },
    { path: '/competitions', component: () => import('../screens/Competitions.js') },
    { path: '/ranks', component: () => import('../screens/Ranks.js') },
    { path: '/divisions', component: () => import('../screens/Divisions.js') },
    { path: '/limit-dates', component: () => import('../screens/LimitDates.js') },
    { path: '/week-schedule', component: () => import('../screens/WeekSchedule.js') },
    { path: '/blacklist-dates', component: () => import('../screens/BlacklistDates.js') },
    { path: '/gymnasium-closures', component: () => import('../screens/GymnasiumClosures.js') },
    { path: '/team-unavailabilities', component: () => import('../screens/TeamUnavailabilities.js') },
    { path: '/incompatible-teams', component: () => import('../screens/IncompatibleTeams.js') },
    { path: '/club-friendships', component: () => import('../screens/ClubFriendships.js') },
    { path: '/city-closures', component: () => import('../screens/CityClosures.js') },
    { path: '/news', component: () => import('../screens/News.js') },
    { path: '/calendar-events', component: () => import('../screens/CalendarEvents.js') },
    { path: '/emails', component: () => import('../screens/Emails.js') },
    { path: '/surveys', component: () => import('../screens/Surveys.js') },
    { path: '/commission', component: () => import('../screens/Commission.js') },
    { path: '/registrations', component: () => import('../screens/Registrations.js') },
    { path: '/registry', component: () => import('../screens/Registry.js') },
    { path: '/indicators', component: () => import('../screens/Indicators.js') },
    { path: '/activity', component: () => import('../screens/Activity.js') },
    { path: '/hall-of-fame', component: () => import('../screens/HallOfFame.js') },
    { path: '/bilan', component: () => import('../screens/Bilan.js') },
    { path: '/:pathMatch(.*)*', redirect: '/users' },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

/**
 * Écrans de l'administration, groupés dans l'ordre du menu.
 *
 * Le regroupement est arrivé avec le lot 3 : à plat, la barre latérale passait
 * de dix à seize entrées, et les lots suivants en ont ajouté d'autres.
 *
 * Une entrée porte soit `path` (route de cette SPA), soit `href` (page à part).
 * `admin/matches.html` est une entrée Vite distincte, antérieure à #265 : elle
 * n'est pas une route d'ici, mais elle doit figurer au menu — la barre d'outils
 * d'`admin.php` était son seul point d'accès, et le lot 6 l'a emportée avec
 * elle.
 */
export const MENU = [
    {
        label: 'Référentiel',
        items: [
            { path: '/users', label: 'Utilisateurs', icon: 'fas fa-users' },
            { path: '/clubs', label: 'Clubs', icon: 'fas fa-sitemap' },
            { path: '/teams', label: 'Équipes', icon: 'fas fa-people-group' },
            { path: '/players', label: 'Joueurs', icon: 'fas fa-person-running' },
            { path: '/gymnasiums', label: 'Gymnases', icon: 'fas fa-building' },
        ],
    },
    {
        label: 'Compétitions',
        items: [
            { path: '/matches', label: 'Matchs', icon: 'fas fa-volleyball' },
            { href: '/admin/matches.html', label: 'Validation des matchs', icon: 'fas fa-clipboard-check' },
            { path: '/competitions', label: 'Compétitions', icon: 'fas fa-trophy' },
            { path: '/ranks', label: 'Divisions / poules', icon: 'fas fa-list-ol' },
            { path: '/divisions', label: 'Réorganiser les divisions', icon: 'fas fa-arrows-up-down-left-right' },
            { path: '/limit-dates', label: 'Dates limites', icon: 'fas fa-hourglass-end' },
            { path: '/week-schedule', label: 'Planning semaine', icon: 'fas fa-calendar-week' },
        ],
    },
    {
        label: 'Planification',
        items: [
            { path: '/blacklist-dates', label: 'Dates interdites', icon: 'fas fa-calendar-xmark' },
            { path: '/gymnasium-closures', label: 'Fermetures de gymnase', icon: 'fas fa-door-closed' },
            { path: '/team-unavailabilities', label: "Indispos d'équipe", icon: 'fas fa-user-clock' },
            { path: '/incompatible-teams', label: 'Équipes incompatibles', icon: 'fas fa-ban' },
            { path: '/club-friendships', label: 'Ententes entre clubs', icon: 'fas fa-handshake' },
            { path: '/city-closures', label: 'Périodes par ville', icon: 'fas fa-city' },
        ],
    },
    {
        label: 'Contenus',
        items: [
            { path: '/news', label: 'News', icon: 'fas fa-newspaper' },
            { path: '/calendar-events', label: 'Calendrier de la home', icon: 'fas fa-calendar-days' },
        ],
    },
    {
        label: 'Communication & suivi',
        items: [
            { path: '/emails', label: 'Emails', icon: 'fas fa-envelope' },
            { path: '/surveys', label: 'Sondages', icon: 'fas fa-square-poll-vertical' },
            { path: '/commission', label: 'Commission', icon: 'fas fa-users-gear' },
            { path: '/registrations', label: 'Inscriptions', icon: 'fas fa-clipboard-check' },
            { path: '/registry', label: 'Base de registres', icon: 'fas fa-database' },
        ],
    },
    {
        label: 'Consultation & outils',
        items: [
            { path: '/indicators', label: 'Indicateurs', icon: 'fas fa-gauge-high' },
            { path: '/activity', label: 'Activité', icon: 'fas fa-clock-rotate-left' },
            { path: '/hall-of-fame', label: 'Palmarès', icon: 'fas fa-medal' },
            { path: '/bilan', label: 'Bilan annuel', icon: 'fas fa-file-pdf' },
        ],
    },
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
