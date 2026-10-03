/**
 * Accueil — inscriptions en cours, visibles de tous (issue #379).
 *
 * Par compétition, de l'ouverture des inscriptions au démarrage : les équipes
 * qui ont demandé leur inscription (statut, nouvelle ou réengagement) et, en
 * championnat, celles du classement actuel qui ne se sont pas réinscrites.
 * L'API (`register/getPublicRegistrations`) ne renvoie que le club, l'équipe,
 * le statut, le type et l'ancien nom : rien sur les personnes.
 */
const STATUSES = {
    VALIDATED: {label: 'validée', plural: 'validées', badge: 'badge-success', icon: 'fa-check'},
    PENDING: {label: 'en attente', plural: 'en attente', badge: 'badge-warning', icon: 'fa-hourglass-half'},
    REFUSED: {label: 'refusée', plural: 'refusées', badge: 'badge-error', icon: 'fa-ban'},
    NOT_REGISTERED: {label: 'pas réinscrite', plural: 'pas réinscrites', badge: 'badge-ghost', icon: 'fa-user-slash'},
};

export default {
    template: `
      <section v-if="competitions.length" class="w-full max-w-5xl" data-testid="public-registrations">
        <h2 class="text-2xl font-bold text-center mb-1">Inscriptions en cours</h2>
        <p class="text-sm text-center text-base-content/70 mb-4">
          Les équipes qui ont demandé leur inscription pour la prochaine saison, et celles du classement actuel
          qui ne se sont pas encore réinscrites.
        </p>
        <div class="flex flex-col gap-2">
          <div v-for="c in competitions" :key="c.code_competition"
               class="collapse collapse-arrow bg-base-100 border border-base-300"
               :data-competition="c.code_competition">
            <input type="checkbox" :aria-label="'Afficher les inscriptions : ' + c.libelle"/>
            <div class="collapse-title flex flex-wrap items-center gap-x-3 gap-y-1 pr-10">
              <span class="font-semibold">{{ c.libelle }}</span>
              <span v-if="c.limit_register_date" class="text-xs text-base-content/60">
                inscriptions jusqu'au {{ c.limit_register_date }}
              </span>
              <span class="flex flex-wrap gap-1 ml-auto">
                <span v-for="s in summary(c)" :key="s.key" class="badge badge-sm gap-1" :class="s.badge">
                  {{ s.count }} {{ s.count > 1 ? s.plural : s.label }}
                </span>
                <span v-if="newTeams(c)" class="badge badge-sm badge-info badge-outline" data-testid="public-registrations-new">
                  {{ newTeams(c) }} {{ newTeams(c) > 1 ? 'nouvelles équipes' : 'nouvelle équipe' }}
                </span>
                <span v-if="!c.teams.length" class="badge badge-sm badge-ghost">aucune inscription pour l'instant</span>
              </span>
            </div>
            <div class="collapse-content">
              <ul class="divide-y divide-base-200">
                <li v-for="(t, i) in c.teams" :key="i" class="py-2 flex flex-wrap items-center gap-x-3 gap-y-1"
                    data-testid="public-registration">
                  <div class="min-w-0 flex-1">
                    <div class="font-medium break-words">{{ t.equipe }}</div>
                    <div class="text-xs text-base-content/60 break-words">
                      {{ t.club || 'club non renseigné' }}
                      <span v-if="t.ancien_nom">· anciennement {{ t.ancien_nom }}</span>
                    </div>
                  </div>
                  <span v-if="t.type === 'new'" class="badge badge-sm badge-info badge-outline">nouvelle équipe</span>
                  <span v-else-if="t.type === 'renewal'" class="badge badge-sm badge-outline">réengagement</span>
                  <span class="badge badge-sm gap-1" :class="status(t).badge" data-testid="public-registration-status">
                    <i class="fas" :class="status(t).icon"></i>{{ status(t).label }}
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    `,
    data() {
        return {
            competitions: [],
        };
    },
    methods: {
        status(team) {
            return STATUSES[team.status] || STATUSES.PENDING;
        },
        // Compteurs par statut, dans l'ordre de STATUSES, sans les zéros.
        summary(competition) {
            return Object.entries(STATUSES)
                .map(([key, s]) => ({...s, key, count: competition.teams.filter((t) => t.status === key).length}))
                .filter((s) => s.count > 0);
        },
        // Nouvelles équipes à venir : une demande refusée n'en amène pas.
        newTeams(competition) {
            return competition.teams.filter((t) => t.type === 'new' && t.status !== 'REFUSED').length;
        },
    },
    created() {
        axios.get('/rest/action.php/register/getPublicRegistrations')
            .then(({data}) => {
                this.competitions = Array.isArray(data) ? data : [];
            })
            .catch((error) => {
                console.error('Erreur lors du chargement des inscriptions :', error);
            });
    },
};
