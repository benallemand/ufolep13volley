/**
 * Encart d'alertes du tableau de bord (issue #346), pour le responsable
 * d'équipe comme pour le responsable de club : une section par équipe, une
 * carte par alerte, avec un lien vers l'écran où la corriger.
 */
const HELP = {
    showHelpSelectLeader: "Dans la gestion des joueurs, désignez un responsable d'équipe.",
    showHelpSelectViceLeader: "Dans la gestion des joueurs, désignez un suppléant au responsable (optionnel).",
    showHelpSelectCaptain: "Dans la gestion des joueurs, désignez le capitaine de l'équipe.",
    showHelpSelectTimeSlot: "Dans la gestion des gymnases, indiquez les créneaux où vous pouvez recevoir.",
    showHelpAddPhoneNumber: "Éditez le responsable ou le suppléant et ajoutez au moins un numéro de téléphone.",
    showHelpAddEmail: "Éditez le responsable ou le suppléant et ajoutez au moins une adresse email.",
    showHelpAddPlayer: "Ajoutez un joueur connu du site, ou créez-le s'il n'existe pas encore.",
    showHelpPlayersWithoutPhoto: "Un joueur sans photo ne peut pas jouer : ajoutez sa photo depuis la gestion des joueurs.",
    showHelpPlayersWithoutLicenceNumber: "Renseignez le numéro de licence dès que vous l'avez : sans lui, la commission ne peut pas valider le joueur.",
    showHelpInactivePlayers: "La commission doit encore valider ces licences. Si le délai vous semble long, relancez le responsable de la compétition.",
    showHelpMatchAction: "Action à faire sur la page du match.",
    showHelpPenalty: "Pensez à signer la feuille de match en ligne après chaque rencontre. En cas d'erreur, contactez la commission.",
};

const BADGE = {error: 'badge-error', warning: 'badge-warning', info: 'badge-info'};
const LABEL = {error: 'À corriger', warning: 'À surveiller', info: 'Info'};

export default {
    template: `
      <div class="mb-6" data-testid="alerts-panel">
        <p class="text-xl mb-2"><i class="fas fa-bell mr-2"></i>Alertes</p>
        <div v-if="loaded && alerts.length === 0" class="alert alert-success" data-testid="alerts-none">
          <i class="fas fa-circle-check"></i><span>Rien à signaler.</span>
        </div>
        <div v-for="group in groups" :key="group.team" class="bg-base-200 border border-base-300 rounded-box p-4 mb-3">
          <h3 v-if="groups.length > 1 || group.team" class="font-bold mb-2">{{ group.team }}</h3>
          <div class="flex flex-wrap gap-2">
            <div v-for="alert in group.alerts" :key="alert.issue" class="card bg-base-100 w-full md:w-96 shadow"
                 data-testid="alert-card">
              <div class="card-body p-4">
                <div class="flex items-start gap-2">
                  <span class="badge whitespace-nowrap shrink-0" :class="badge(alert)">{{ label(alert) }}</span>
                  <h2 class="font-semibold">{{ alert.issue }}</h2>
                </div>
                <p class="text-sm">{{ help(alert) }}</p>
                <div v-if="alert.link" class="card-actions justify-end">
                  <a :href="alert.link" class="btn btn-sm btn-primary">Corriger</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            alerts: [],
            loaded: false,
        };
    },
    computed: {
        // erreurs d'abord, puis avertissements, puis informations
        groups() {
            const order = {error: 0, warning: 1, info: 2};
            const byTeam = new Map();
            for (const alert of this.alerts) {
                const team = alert.team || '';
                if (!byTeam.has(team)) {
                    byTeam.set(team, []);
                }
                byTeam.get(team).push(alert);
            }
            return [...byTeam.entries()].map(([team, alerts]) => ({
                team,
                alerts: alerts.sort((a, b) => (order[a.criticity] ?? 3) - (order[b.criticity] ?? 3)),
            }));
        },
    },
    methods: {
        badge(alert) {
            return BADGE[alert.criticity] || 'badge-ghost';
        },
        label(alert) {
            return LABEL[alert.criticity] || '';
        },
        help(alert) {
            return HELP[alert.expected_action] || '';
        },
        fetchAlerts() {
            axios
                .get("/rest/action.php/alerts/getAlerts")
                .then((response) => {
                    this.alerts = Array.isArray(response.data) ? response.data : [];
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des alertes :", error);
                })
                .finally(() => {
                    this.loaded = true;
                });
        },
    },
    created() {
        this.fetchAlerts();
    },
};
