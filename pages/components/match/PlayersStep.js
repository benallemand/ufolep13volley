/**
 * Étape « Présents » de la page de match (#490), reprise de l'ancienne page
 * des fiches équipes.
 *
 * Liste commune : chacun des deux responsables coche les joueurs présents des
 * deux équipes, tant que personne n'a signé la fiche équipes. Photo
 * obligatoire (#343) et règles des renforts (#348, #349) appliquées, sauf pour
 * l'admin qui corrige une fiche. L'enregistrement est fait par la page, avec
 * la version du match : il ne peut pas écraser une saisie de l'autre équipe.
 */
import axios from 'axios';
import {onError} from '/toaster.js';

export default {
    props: {
        matchData: {type: Object, required: true},
        idMatch: {type: [String, Number], required: true},
        isAdmin: {type: Boolean, default: false},
        // équipe du compte : les renforts ne sont que pour elle (#348)
        myTeamId: {type: Number, default: null},
        frozen: {type: Boolean, default: false},
        // change à chaque rechargement du match : relit les listes
        reloadKey: {type: Number, default: 0},
    },
    emits: ['save'],
    data() {
        return {
            available: [],
            present: [],
            renforts: [],
            query: '',
            renfortTeam: null,
            dirty: false,
            loading: false,
        };
    },
    watch: {
        reloadKey() {
            this.load();
        },
    },
    mounted() {
        this.load();
    },
    computed: {
        teams() {
            return ['dom', 'ext'].map(side => ({
                side,
                id: Number(this.matchData[`id_equipe_${side}`]),
                name: this.matchData[`equipe_${side}`],
            }));
        },
        teamNames() {
            return Object.fromEntries(this.teams.map(t => [t.id, t.name]));
        },
        // équipes qu'on peut renforcer : les deux pour l'admin, la sienne sinon (#348)
        renfortTeams() {
            if (this.isAdmin) return this.teams;
            const own = this.teams.filter(t => t.id === this.myTeamId);
            return own.length ? own : this.teams;
        },
        presentRenforts() {
            return this.present.filter(p => !this.isTeamPlayer(p));
        },
        renfortResults() {
            const ids = new Set(this.present.map(p => Number(p.id)));
            return this.renforts.filter(p => !ids.has(Number(p.id)));
        },
    },
    methods: {
        isTeamPlayer(player) {
            return player.equipe === this.matchData.equipe_dom || player.equipe === this.matchData.equipe_ext;
        },
        isPresent(player) {
            return this.present.some(p => Number(p.id) === Number(player.id));
        },
        // les joueurs d'une équipe, présents d'abord, puis par nom
        rosterOf(team) {
            const byName = (a, b) => `${a.nom} ${a.prenom}`.localeCompare(`${b.nom} ${b.prenom}`, 'fr');
            const present = this.present.filter(p => p.equipe === team.name).sort(byName);
            if (this.frozen) return present;
            return present.concat(this.available.filter(p => p.equipe === team.name && !this.isPresent(p)).sort(byName));
        },
        countOf(team) {
            return this.present.filter(p => p.equipe === team.name).length;
        },
        isBlocked(player) {
            return !this.isAdmin && (Number(player.has_photo) === 0 || !!player.reinforcement_blocked);
        },
        notHomologated(player) {
            const parse = s => {
                const [d, m, y] = (s || '').split('/');
                return y ? new Date(`${y}-${m}-${d}`) : null;
            };
            const match = parse(player.date_reception);
            const homologation = parse(player.date_homologation);
            return Number(player.est_actif) === 1 && match && homologation && match < homologation;
        },
        reinforcedTeam(player) {
            const id = player.renfort_for ?? player.id_team_reinforced;
            return id ? (this.teamNames[id] || '') : '';
        },
        toggle(player) {
            if (this.frozen) return;
            if (this.isPresent(player)) {
                this.present = this.present.filter(p => Number(p.id) !== Number(player.id));
            } else {
                if (this.isBlocked(player)) return;
                this.present.push(player);
            }
            this.dirty = true;
        },
        addRenfort(player) {
            if (this.isBlocked(player)) return;
            player.renfort_for = this.renfortTeam || (this.renfortTeams[0] && this.renfortTeams[0].id);
            this.present.push(player);
            this.query = '';
            this.renforts = [];
            this.dirty = true;
        },
        search() {
            if (this.query.length <= 3) {
                this.renforts = [];
                return;
            }
            // params : la recherche est encodée (un `&` ou un `#` tronquait l'URL)
            axios.get('/rest/action.php/matchmgr/getReinforcementPlayers',
                {params: {id_match: this.idMatch, query: this.query}})
                .then(response => {
                    this.renforts = response.data;
                })
                .catch(error => {
                    this.renforts = [];
                    onError(this, error);
                });
        },
        load() {
            this.loading = true;
            return Promise.all([
                axios.get(`/rest/action.php/matchmgr/getNotMatchPlayers?id_match=${this.idMatch}`),
                axios.get(`/rest/action.php/matchmgr/getMatchPlayers?id_match=${this.idMatch}`),
            ]).then(([available, present]) => {
                this.available = available.data;
                this.present = present.data;
                this.dirty = false;
                if (!this.renfortTeam && this.renfortTeams.length) {
                    this.renfortTeam = this.renfortTeams[0].id;
                }
            }).catch(error => onError(this, error))
                .finally(() => {
                    this.loading = false;
                });
        },
        save() {
            const reinforcements = {};
            this.present.forEach(player => {
                // équipe renforcée (#348) ; un renfort saisi avant #348 n'en a
                // pas : il est rattaché à l'équipe choisie dans « Renfort pour »
                if (!this.isTeamPlayer(player)) {
                    const team = player.renfort_for ?? player.id_team_reinforced ?? this.renfortTeam;
                    if (team) reinforcements[player.id] = team;
                }
            });
            this.$emit('save', {player_ids: this.present.map(p => p.id), reinforcements});
        },
    },
    template: `
      <div data-testid="players-step">
        <div v-if="frozen" class="alert alert-success mb-3 text-sm" data-testid="players-frozen">
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          <span><slot name="frozen-reason">Présents figés.</slot></span>
        </div>
        <p v-else class="text-sm mb-3">
          Liste commune : chacun des deux responsables peut cocher les joueurs des <strong>deux</strong> équipes,
          tant que personne n'a signé la fiche équipes.
        </p>

        <div v-if="loading" class="flex items-center gap-2 text-sm opacity-70 mb-3" data-testid="players-loading">
          <span class="loading loading-spinner loading-sm"></span>Chargement des joueurs…
        </div>
        <div class="grid gap-4 md:grid-cols-2" :class="{'opacity-50': loading}">
          <div v-for="team in teams" :key="team.side" :data-testid="'roster-' + team.side">
            <h3 class="font-semibold mb-2">{{ team.name }} · {{ countOf(team) }} présent(s)</h3>
            <ul class="space-y-2">
              <li v-for="player in rosterOf(team)" :key="player.id"
                  class="player-row flex items-center gap-3 rounded-box border border-base-300 p-2"
                  :class="{'bg-success/10 border-success': isPresent(player)}">
                <img :src="player.path_photo_low" alt="" class="w-10 h-10 rounded-full object-cover shrink-0"/>
                <div class="min-w-0 flex-1">
                  <div class="truncate" :class="{'text-error': Number(player.est_actif) === 0}">
                    {{ player.prenom }} {{ player.nom }}
                  </div>
                  <div class="text-xs text-error">
                    <span v-if="Number(player.est_actif) === 0">Licence non envoyée. </span>
                    <span v-if="Number(player.has_photo) === 0" data-testid="missing-photo">
                      Photo manquante{{ isBlocked(player) && !isPresent(player) ? ' : ne peut pas jouer' : '' }}.
                    </span>
                    <span v-if="notHomologated(player)">Non homologué le jour du match.</span>
                  </div>
                </div>
                <button v-if="!frozen" type="button" class="btn btn-sm shrink-0 min-w-24"
                        :class="isPresent(player) ? 'btn-success' : 'btn-outline'"
                        :disabled="!isPresent(player) && isBlocked(player)"
                        :aria-pressed="isPresent(player)"
                        :aria-label="(isPresent(player) ? 'Retirer ' : 'Ajouter ') + player.prenom + ' ' + player.nom"
                        @click="toggle(player)">
                  <i class="fa-solid" :class="isPresent(player) ? 'fa-check' : 'fa-plus'" aria-hidden="true"></i>
                  {{ isPresent(player) ? 'présent' : 'ajouter' }}
                </button>
              </li>
              <li v-if="!rosterOf(team).length" class="text-sm opacity-60">Aucun joueur.</li>
            </ul>
          </div>
        </div>

        <div class="mt-4 rounded-box border border-base-300 p-3" data-testid="renforts">
          <h3 class="font-semibold mb-2">Renforts</h3>
          <ul v-if="presentRenforts.length" class="space-y-2 mb-3">
            <li v-for="player in presentRenforts" :key="player.id"
                class="flex items-center gap-3 rounded-box border border-success bg-success/10 p-2">
              <img :src="player.path_photo_low" alt="" class="w-10 h-10 rounded-full object-cover shrink-0"/>
              <div class="min-w-0 flex-1">
                <div class="truncate">{{ player.prenom }} {{ player.nom }}</div>
                <div v-if="reinforcedTeam(player)" class="text-xs" data-testid="renfort-for">renfort de {{ reinforcedTeam(player) }}</div>
              </div>
              <button v-if="!frozen" type="button" class="btn btn-sm btn-success shrink-0 min-w-24"
                      :aria-label="'Retirer ' + player.prenom + ' ' + player.nom" @click="toggle(player)">
                <i class="fa-solid fa-check" aria-hidden="true"></i> présent
              </button>
            </li>
          </ul>
          <template v-if="!frozen">
            <label class="flex flex-wrap items-center gap-2 mb-2 text-sm">Renfort pour
              <select v-model.number="renfortTeam" class="select select-sm" aria-label="Équipe renforcée"
                      data-testid="renfort-team">
                <option v-for="team in renfortTeams" :key="team.id" :value="team.id">{{ team.name }}</option>
              </select>
            </label>
            <input v-model="query" @input="search" type="search" class="input w-full mb-2"
                   placeholder="Chercher un renfort (4 lettres minimum)" aria-label="Chercher un renfort"/>
            <div role="note" class="text-xs opacity-80 mb-2">
              Un renfort par match et par équipe, seulement pour compléter l'équipe jusqu'à
              6 joueurs (masculin) ou 4 (féminin, mixte), en respectant la mixité.
              Un même renfort ne joue qu'un match par demi-saison (coupure au 1er janvier).
            </div>
            <ul class="space-y-2">
              <li v-for="player in renfortResults" :key="player.id"
                  class="flex items-center gap-3 rounded-box border border-base-300 p-2">
                <img :src="player.path_photo_low" alt="" class="w-10 h-10 rounded-full object-cover shrink-0"/>
                <div class="min-w-0 flex-1">
                  <div class="truncate">{{ player.prenom }} {{ player.nom }} <span class="text-xs opacity-70">({{ player.equipe }})</span></div>
                  <div class="text-xs text-error">
                    <span v-if="Number(player.has_photo) === 0" data-testid="missing-photo">Photo manquante. </span>
                    <span v-if="player.reinforcement_blocked" data-testid="reinforcement-blocked">Renfort impossible : {{ player.reinforcement_blocked }}</span>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline shrink-0 min-w-24" :disabled="isBlocked(player)"
                        :aria-label="'Ajouter ' + player.prenom + ' ' + player.nom" @click="addRenfort(player)">
                  <i class="fa-solid fa-plus" aria-hidden="true"></i> ajouter
                </button>
              </li>
            </ul>
          </template>
          <p v-else-if="!presentRenforts.length" class="text-sm opacity-60">Aucun renfort.</p>
        </div>

        <div v-if="!frozen" class="sticky bottom-2 mt-4 z-10">
          <button type="button" class="btn btn-primary w-full shadow" data-testid="save-players" @click="save()">
            <i class="fas fa-floppy-disk" aria-hidden="true"></i>
            Enregistrer les présents
            <span v-if="dirty" class="badge badge-sm">modifiés</span>
          </button>
        </div>
      </div>
    `,
};
