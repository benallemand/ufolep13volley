import { createApp } from 'vue';
import axios from 'axios';
import Toastify from 'toastify-js';
import { Notyf } from 'notyf';
import {onSuccess, onError} from "./toaster.js";
import {genericSignMatch, genericSignSheet} from "./signer.js";
import {requireMatchAccess} from "./pages/components/auth/guard.js";
import MatchMenu from "./pages/components/match/MatchMenu.js";
import MatchSummary from "./pages/components/match/MatchSummary.js";

window.axios = axios;
window.Toastify = Toastify;
window.Notyf = Notyf;


const PlayerList = {
    props: {
        players: {type: Array, required: true},
        teamName: {type: String, default: ''},
        isSigned: {type: Boolean, default: false},
        // 'add' : bouton vert "+" qui emet add-player
        // 'remove' : bouton rouge "poubelle" qui emet remove-player
        mode: {type: String, default: 'add'},
        // Règles d'ajout — photo obligatoire (#343), éligibilité du renfort
        // (#349) : faux pour l'admin, qui corrige une fiche.
        enforceRules: {type: Boolean, default: false},
        // id_equipe => nom, pour afficher l'équipe qu'un renfort renforce (#348)
        teamNames: {type: Object, default: () => ({})},
    },
    emits: ['add-player', 'remove-player'],
    methods: {
        isBlocked(player) {
            return this.mode === 'add' && this.enforceRules
                && (Number(player.has_photo) === 0 || !!player.reinforcement_blocked);
        },
        reinforcedTeam(player) {
            const id = player.renfort_for ?? player.id_team_reinforced;
            return id ? (this.teamNames[id] || '') : '';
        },
        handleClick(player) {
            this.$emit(this.mode === 'add' ? 'add-player' : 'remove-player', player);
        },
        parseDate(dateString) {
            if (!dateString) {
                return null;
            }
            const [day, month, year] = dateString.split('/');
            return new Date(`${year}-${month}-${day}`);
        },
        compareDates(date1, date2) {
            const d1 = this.parseDate(date1);
            const d2 = this.parseDate(date2);
            if (!d1 || !d2) {
                return false;
            }
            return d1 < d2;
        },

    },
    template: `
      <div class="border border-2 border-black p-4">
        <h1>{{ teamName }}</h1>
        <div v-for="player in players" :key="player.id" class="flex items-center mb-2">
          <img :src="player.path_photo_low" alt="photo" class="w-12 h-12 rounded-full mr-3"/>
          <span :class="{'bg-pink-500 text-white': player.est_actif === 0}" class="px-2 py-1 rounded">
                        {{ player.prenom }} {{ player.nom }}
                    </span>
          <span v-if="player.est_actif === 0" class="text-sm text-red-500 ml-2">(Licence non envoyée)</span>
          <span v-if="Number(player.has_photo) === 0" class="text-sm text-red-500 ml-2" data-testid="missing-photo">
            (Photo manquante{{ isBlocked(player) ? ' : ne peut pas jouer' : '' }})
          </span>
          <span v-if="mode === 'remove' && reinforcedTeam(player)" class="text-sm ml-2" data-testid="renfort-for">
            (renfort de {{ reinforcedTeam(player) }})
          </span>
          <span v-if="mode === 'add' && player.reinforcement_blocked" class="text-sm text-red-500 ml-2"
                data-testid="reinforcement-blocked">
            (Renfort impossible : {{ player.reinforcement_blocked }})
          </span>
          <span
              v-if="player.est_actif === 1 && player.date_reception && player.date_homologation && compareDates(player.date_reception, player.date_homologation)"
              class="text-sm text-red-500 ml-2">(Non homologué le jour du match)</span>
          <button v-if="!isSigned"
                  :disabled="isBlocked(player)"
                  :title="isBlocked(player) ? 'Ajout impossible : voir le motif affiché' : ''"
                  class="btn ml-auto"
                  :class="{'btn-success': mode === 'add', 'btn-error': mode === 'remove'}"
                  @click="handleClick(player)">
            <i class="fa-solid"
               :class="{'fa-plus': mode === 'add', 'fa-trash': mode === 'remove'}"></i>
          </button>
        </div>
      </div>
    `
};

createApp({
    components: {
        'player-list': PlayerList,
        'match-menu': MatchMenu,
        'match-summary': MatchSummary,
    },
    data() { return {
        id_match: (new URLSearchParams(window.location.search)).get('id_match'),
        matchData: {},
        availablePlayers: [],
        matchPlayers: [],
        isLoading: false,
        query: '',
        renforts: [],
        isAdmin: false,
        userTeamId: null,
        renfortTeam: null,
    }; },
    computed: {
        teamNames() {
            return {
                [this.matchData.id_equipe_dom]: this.matchData.equipe_dom,
                [this.matchData.id_equipe_ext]: this.matchData.equipe_ext,
            };
        },
        // Équipes qu'on peut renforcer : les deux pour l'admin, la sienne sinon (#348).
        renfortTeams() {
            const teams = [
                {id: Number(this.matchData.id_equipe_dom), name: this.matchData.equipe_dom},
                {id: Number(this.matchData.id_equipe_ext), name: this.matchData.equipe_ext},
            ].filter(team => team.id);
            if (this.isAdmin) {
                return teams;
            }
            const own = teams.filter(team => team.id === Number(this.userTeamId));
            return own.length ? own : teams;
        },
        availablePlayersDom() {
            return this.availablePlayers.filter(player => player.equipe === this.matchData.equipe_dom && !this.matchPlayers.includes(player));
        },
        availablePlayersExt() {
            return this.availablePlayers.filter(player => player.equipe === this.matchData.equipe_ext && !this.matchPlayers.includes(player));
        },
    },
    async created() {
        // Contrôle d'accès côté client (remplace les vérifs PHP de team_sheets.php)
        const user = await requireMatchAccess(this.id_match, ['admin', 'team_leader']);
        if (!user) {
            return; // redirection déjà déclenchée par la garde
        }
        this.isAdmin = !!user.is_admin;
        this.userTeamId = user.id_equipe ? Number(user.id_equipe) : null;
        this.reloadData();
    },
    methods: {
        search() {
            if (this.query.length > 3) {
                // params : la recherche est encodée (un `&` ou un `#` tronquait l'URL)
                return axios.get('/rest/action.php/matchmgr/getReinforcementPlayers',
                    {params: {id_match: this.id_match, query: this.query}})
                    .then(response => {
                        this.renforts = response.data;
                    })
                    .catch(error => {
                        this.renforts = [];
                        onError(this, error)
                    });
            }
            this.renforts = [];
        },
        loadMatchData() {
            return axios.get(`/rest/action.php/matchmgr/get_match?id_match=${this.id_match}`)
                .then(response => {
                    this.matchData = response.data;
                    if (!this.renfortTeam && this.renfortTeams.length) {
                        this.renfortTeam = this.renfortTeams[0].id;
                    }
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        loadAvailablePlayers() {
            return axios.get(`/rest/action.php/matchmgr/getNotMatchPlayers?id_match=${this.id_match}`)
                .then(response => {
                    this.availablePlayers = response.data;
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        loadMatchPlayers() {
            return axios.get(`/rest/action.php/matchmgr/getMatchPlayers?id_match=${this.id_match}`)
                .then(response => {
                    this.matchPlayers = response.data;
                })
                .catch(error => {
                    onError(this, error)
                });
        },
        signMatch() {
            genericSignMatch(this, this.id_match);
        },
        signTeamSheets() {
            genericSignSheet(this, this.id_match);
        },
        addRenfort(player) {
            player.renfort_for = this.renfortTeam || (this.renfortTeams[0] && this.renfortTeams[0].id);
            this.addPlayer(player);
        },
        addPlayer(player) {
            if (!this.matchPlayers.includes(player)) {
                this.matchPlayers.push(player);
            }
        },
        removePlayer(player) {
            this.matchPlayers = this.matchPlayers.filter(matchPlayer => matchPlayer !== player);
        },
        reloadData() {
            this.isLoading = true;
            Promise.all([this.loadMatchData(), this.loadAvailablePlayers(), this.loadMatchPlayers()])
                .finally(() => {
                    this.isLoading = false;
                });
        },
        submitForm() {
            const formData = new FormData()
            formData.append('id_match', this.id_match)
            this.matchPlayers.forEach((player) => {
                formData.append('player_ids[]', player.id)
                // équipe renforcée (#348) ; un renfort saisi avant #348 n'en a
                // pas : il est rattaché à l'équipe choisie dans « Renfort pour »
                const isRenfort = player.equipe !== this.matchData.equipe_dom && player.equipe !== this.matchData.equipe_ext;
                if (isRenfort) {
                    const team = player.renfort_for ?? player.id_team_reinforced ?? this.renfortTeam;
                    if (team) {
                        formData.append(`reinforcements[${player.id}]`, team)
                    }
                }
            })
            this.isLoading = true;
            axios.post('/rest/action.php/matchmgr/manage_match_players', formData)
                .then(
                    response => {
                        onSuccess(this, response)
                        this.reloadData()
                    }
                )
                .catch(error => {
                    onError(this, error)
                });
        },
    }
}).mount('#app');
