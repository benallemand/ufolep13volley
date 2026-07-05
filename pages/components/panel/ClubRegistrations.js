import {onError, onSuccess} from "../../../toaster.js";

/**
 * Issue #249 — Inscriptions des équipes par le responsable de club.
 *
 * Liste les demandes d'inscription du club avec leur statut, et propose un
 * formulaire de création/édition. Une demande reste modifiable/supprimable
 * par le club tant qu'elle n'est pas validée par la commission (statut
 * PENDING). Le backend force le club de session et verrouille les demandes
 * validées.
 */
const DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
const HOURS = ['19:00', '19:15', '19:30', '19:45', '20:00', '20:15', '20:30', '20:45', '21:00', '21:15', '21:30', '21:45'];

function emptyForm() {
    return {
        id: null,
        new_team_name: '',
        id_competition: '',
        old_team_id: '',
        leader_name: '',
        leader_first_name: '',
        leader_email: '',
        leader_phone: '',
        id_court_1: '',
        day_court_1: '',
        hour_court_1: '',
        id_court_2: '',
        day_court_2: '',
        hour_court_2: '',
        is_cup_registered: false,
        is_seeding_tournament_requested: false,
        can_seeding_tournament_setup: false,
        remarks: '',
    };
}

// dates au format dd/mm/yyyy renvoyées par l'API
function parseFrDate(str) {
    if (!str) return null;
    const [d, m, y] = str.split('/');
    return new Date(parseInt(y), parseInt(m) - 1, parseInt(d));
}

export default {
    template: `
      <div>
        <p class="text-xl">Inscriptions aux compétitions</p>
        <p class="text-sm opacity-70 mb-4">
          Déposez ici les demandes d'inscription des équipes de votre club. Une demande reste
          modifiable tant qu'elle n'a pas été validée par la commission.
        </p>

        <!-- Fenêtres d'inscription -->
        <div class="overflow-x-auto mb-6" v-if="openCompetitions.length > 0">
          <table class="table table-zebra table-sm w-auto">
            <thead>
            <tr><th>Compétition ouverte</th><th>Ouverture</th><th>Fermeture</th></tr>
            </thead>
            <tbody>
            <tr v-for="c in openCompetitions" :key="c.id">
              <td>{{ c.libelle }}</td>
              <td>{{ c.start_register_date }}</td>
              <td>{{ c.limit_register_date }}</td>
            </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="alert alert-warning mb-6">
          <i class="fas fa-triangle-exclamation"></i>
          <span>Aucune compétition n'est ouverte aux inscriptions actuellement.</span>
        </div>

        <!-- Demandes du club -->
        <div class="flex flex-wrap gap-4 mb-6">
          <div v-for="r in registrations" :key="r.id" class="card shadow-xl w-full md:w-96 bg-base-100">
            <div class="card-body">
              <h2 class="card-title"><i class="fas fa-volleyball mr-2"></i>{{ r.new_team_name }}</h2>
              <p class="text-sm">{{ r.competition }}</p>
              <p class="text-sm opacity-70" v-if="r.old_team">reconduction de {{ r.old_team }}</p>
              <p class="text-sm opacity-70">
                <i class="fas fa-user mr-1"></i>{{ r.leader_first_name }} {{ r.leader_name }}
              </p>
              <div>
                <span v-if="r.status === 'VALIDATED'" class="badge badge-success gap-1">
                  <i class="fas fa-check"></i>validée le {{ r.validation_date }}
                </span>
                <span v-else class="badge badge-warning gap-1">
                  <i class="fas fa-hourglass-half"></i>en attente de validation
                </span>
              </div>
              <div v-if="r.status !== 'VALIDATED'" class="card-actions justify-end">
                <button class="btn btn-sm btn-primary" @click="onEditClick(r)">
                  <i class="fas fa-pen mr-1"></i>modifier
                </button>
                <button class="btn btn-sm btn-error" @click="onDeleteClick(r)">
                  <i class="fas fa-trash mr-1"></i>supprimer
                </button>
              </div>
            </div>
          </div>
          <p v-if="registrations.length === 0" class="opacity-60">
            Aucune demande d'inscription pour votre club cette saison.
          </p>
        </div>

        <!-- Formulaire -->
        <form v-if="openCompetitions.length > 0"
              class="flex flex-col gap-3 p-4 max-w-xl border rounded-lg shadow-lg bg-base-100"
              @submit.prevent="handleSubmit">
          <p class="font-bold text-lg">
            {{ form.id ? 'Modifier la demande' : "Nouvelle demande d'inscription" }}
          </p>

          <label class="font-bold" for="id_competition">Compétition</label>
          <select id="id_competition" v-model="form.id_competition" class="select select-bordered" required
                  @change="onCompetitionChange">
            <option value="">Sélectionner une compétition</option>
            <option v-for="c in openCompetitions" :key="c.id" :value="c.id">{{ c.libelle }}</option>
          </select>

          <div class="flex flex-wrap gap-4">
            <label class="label cursor-pointer justify-start gap-2">
              <input type="radio" value="renew" v-model="mode" class="radio" @change="onModeChange"/>
              <span class="font-bold">Réengager une équipe du club</span>
            </label>
            <label class="label cursor-pointer justify-start gap-2">
              <input type="radio" value="new" v-model="mode" class="radio" @change="onModeChange"/>
              <span class="font-bold">Inscrire une nouvelle équipe</span>
            </label>
          </div>

          <template v-if="mode === 'renew'">
            <label class="font-bold" for="old_team_id">Équipe à réengager</label>
            <select id="old_team_id" v-model="form.old_team_id" class="select select-bordered" required
                    @change="onOldTeamChange">
              <option value="">Sélectionner l'équipe</option>
              <option v-for="t in oldTeamChoices" :key="t.id_equipe" :value="t.id_equipe">{{ t.nom_equipe }}</option>
            </select>
            <p class="text-xs opacity-60 italic">
              Le formulaire est pré-rempli avec les informations de la saison en cours
              (responsable, créneaux) : vérifiez-les et corrigez si besoin.
            </p>
          </template>

          <label class="font-bold" for="new_team_name">Nom de l'équipe à engager</label>
          <input id="new_team_name" type="text" v-model="form.new_team_name" class="input input-bordered" required/>
          <p class="text-xs opacity-60 italic">
            Saisissez un nom pour votre équipe, que ce soit le même que l'an dernier ou pas.
            Le nombre d'équipes est limité par les terrains fournis par votre club
            (1 terrain par semaine = 2 équipes maximum).
          </p>

          <label v-if="isCupChoiceVisible" class="label cursor-pointer justify-start gap-2">
            <input type="checkbox" v-model="form.is_cup_registered" class="checkbox"/>
            <span>Mon équipe souhaite participer à la coupe 6x6 Isoardi</span>
          </label>

          <template v-if="seedingTournamentWeek">
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" v-model="form.is_seeding_tournament_requested" class="checkbox"/>
              <span>Je souhaiterais que cette équipe participe au tournoi de brassage durant la
                {{ seedingTournamentWeek }} (sous réserve d'éligibilité)</span>
            </label>
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" v-model="form.can_seeding_tournament_setup" class="checkbox"/>
              <span>Mon club peut organiser le tournoi de brassage durant la {{ seedingTournamentWeek }}</span>
            </label>
          </template>

          <p class="font-bold mt-2">Responsable pressenti de l'équipe</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input type="text" v-model="form.leader_name" class="input input-bordered" placeholder="Nom" required/>
            <input type="text" v-model="form.leader_first_name" class="input input-bordered" placeholder="Prénom" required/>
            <input type="email" v-model="form.leader_email" class="input input-bordered" placeholder="Email" required/>
            <input type="tel" v-model="form.leader_phone" class="input input-bordered" placeholder="Téléphone" required/>
          </div>

          <p class="font-bold mt-2">Réception principale</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <select v-model="form.id_court_1" class="select select-bordered">
              <option value="">Gymnase principal</option>
              <option v-for="g in gymnasiums" :key="g.id" :value="g.id">{{ g.full_name }}</option>
            </select>
            <select v-model="form.day_court_1" class="select select-bordered">
              <option value="">Jour</option>
              <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
            </select>
            <select v-model="form.hour_court_1" class="select select-bordered">
              <option value="">Heure</option>
              <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
            </select>
          </div>

          <p class="font-bold mt-2">Réception secondaire</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <select v-model="form.id_court_2" class="select select-bordered">
              <option value="">Gymnase secondaire</option>
              <option v-for="g in gymnasiums" :key="g.id" :value="g.id">{{ g.full_name }}</option>
            </select>
            <select v-model="form.day_court_2" class="select select-bordered">
              <option value="">Jour</option>
              <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
            </select>
            <select v-model="form.hour_court_2" class="select select-bordered">
              <option value="">Heure</option>
              <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
            </select>
          </div>
          <p class="text-xs opacity-60 italic">
            Si votre gymnase n'apparaît pas dans la liste, contactez
            <a class="link" href="mailto:contact@ufolep13volley.org">contact@ufolep13volley.org</a>.
          </p>

          <label class="font-bold" for="remarks">Autres infos</label>
          <textarea id="remarks" v-model="form.remarks" class="textarea textarea-bordered"
                    placeholder="Dates d'indisponibilité du gymnase, ou toute info utile pour générer les matchs"></textarea>

          <div class="flex gap-2 justify-end">
            <button v-if="form.id" type="button" class="btn btn-ghost" @click="resetForm">annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="isLoading">
              <i class="fas fa-save mr-1"></i>{{ form.id ? 'enregistrer les modifications' : 'déposer la demande' }}
            </button>
          </div>
        </form>
      </div>
    `,
    data() {
        return {
            registrations: [],
            competitions: [],
            clubTeams: [],
            gymnasiums: [],
            seedingTournamentWeek: '',
            form: emptyForm(),
            mode: 'renew',
            isLoading: false,
            days: DAYS,
            hours: HOURS,
        };
    },
    computed: {
        openCompetitions() {
            const now = new Date();
            return this.competitions.filter((c) => {
                const start = parseFrDate(c.start_register_date);
                const end = parseFrDate(c.limit_register_date);
                if (!start || !end) return false;
                end.setHours(23, 59, 59);
                return start <= now && now <= end;
            });
        },
        selectedCompetition() {
            return this.competitions.find((c) => c.id == this.form.id_competition) || null;
        },
        isCupChoiceVisible() {
            return this.selectedCompetition?.code_competition === 'm';
        },
        // reconduction : équipes du club dans la compétition choisie
        oldTeamChoices() {
            if (!this.selectedCompetition) return [];
            return this.clubTeams.filter((t) => t.code_competition === this.selectedCompetition.code_competition);
        },
    },
    methods: {
        fetchAll() {
            axios.get('/rest/action.php/register/getMyClubRegistrations')
                .then((r) => this.registrations = r.data)
                .catch((e) => onError(this, e));
            axios.get('/rest/action.php/competition/getCompetitions')
                .then((r) => this.competitions = r.data)
                .catch(() => {});
            axios.get('/rest/action.php/club/getMyClubTeams')
                .then((r) => this.clubTeams = r.data)
                .catch(() => {});
            axios.get('/rest/action.php/court/getGymnasiums')
                .then((r) => this.gymnasiums = r.data)
                .catch(() => {});
            axios.get('/rest/action.php/configuration/getRegisterSettings')
                .then((r) => this.seedingTournamentWeek = r.data.seeding_tournament_week || '')
                .catch(() => {});
        },
        onCompetitionChange() {
            this.form.old_team_id = '';
            this.form.is_cup_registered = this.isCupChoiceVisible;
        },
        onModeChange() {
            this.form.old_team_id = '';
        },
        // réengagement : pré-remplit le formulaire avec les infos de l'équipe
        // (nom, responsable, créneaux de la saison en cours)
        onOldTeamChange() {
            if (!this.form.old_team_id) {
                return;
            }
            const team = this.clubTeams.find((t) => t.id_equipe == this.form.old_team_id);
            if (team) {
                this.form.new_team_name = team.nom_equipe;
            }
            axios.get(`/rest/action.php/team/load_register_for_my_club?id_team=${this.form.old_team_id}`)
                .then((r) => {
                    const d = r.data;
                    this.form.leader_name = d.leader_name || '';
                    this.form.leader_first_name = d.leader_first_name || '';
                    this.form.leader_email = d.leader_email || '';
                    this.form.leader_phone = d.leader_phone || '';
                    this.form.id_court_1 = d.id_court_1 || '';
                    this.form.day_court_1 = d.day_court_1 || '';
                    this.form.hour_court_1 = d.hour_court_1 || '';
                    this.form.id_court_2 = d.id_court_2 || '';
                    this.form.day_court_2 = d.day_court_2 || '';
                    this.form.hour_court_2 = d.hour_court_2 || '';
                })
                .catch((error) => onError(this, error));
        },
        onEditClick(r) {
            this.mode = r.old_team_id ? 'renew' : 'new';
            this.form = {
                id: r.id,
                new_team_name: r.new_team_name || '',
                id_competition: r.id_competition || '',
                old_team_id: r.old_team_id || '',
                leader_name: r.leader_name || '',
                leader_first_name: r.leader_first_name || '',
                leader_email: r.leader_email || '',
                leader_phone: r.leader_phone || '',
                id_court_1: r.id_court_1 || '',
                day_court_1: r.day_court_1 || '',
                hour_court_1: r.hour_court_1 || '',
                id_court_2: r.id_court_2 || '',
                day_court_2: r.day_court_2 || '',
                hour_court_2: r.hour_court_2 || '',
                is_cup_registered: !!parseInt(r.is_cup_registered),
                is_seeding_tournament_requested: !!parseInt(r.is_seeding_tournament_requested),
                can_seeding_tournament_setup: !!parseInt(r.can_seeding_tournament_setup),
                remarks: r.remarks || '',
            };
            window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
        },
        resetForm() {
            this.form = emptyForm();
            this.mode = 'renew';
        },
        handleSubmit() {
            const formData = new FormData();
            Object.entries(this.form).forEach(([key, value]) => {
                if (typeof value === 'boolean') {
                    formData.append(key, value ? 'on' : 'off');
                } else {
                    formData.append(key, value === null ? '' : value);
                }
            });
            // le backend force le club de session : la valeur postée est ignorée
            formData.append('id_club', '');
            this.isLoading = true;
            axios.post('/rest/action.php/register/register', formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.resetForm();
                    this.fetchAll();
                })
                .catch((error) => onError(this, error))
                .finally(() => this.isLoading = false);
        },
        onDeleteClick(r) {
            if (!confirm("Supprimer la demande d'inscription de « " + r.new_team_name + " » ?")) {
                return;
            }
            const formData = new FormData();
            formData.append('id', r.id);
            axios.post('/rest/action.php/register/deleteMyClubRegistration', formData)
                .then((response) => {
                    onSuccess(this, response);
                    if (this.form.id === r.id) {
                        this.resetForm();
                    }
                    this.fetchAll();
                })
                .catch((error) => onError(this, error));
        },
    },
    created() {
        this.fetchAll();
    },
};
