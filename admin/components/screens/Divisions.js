import { onError, onSuccess } from '../../../toaster.js';

/**
 * Réorganisation des divisions par glisser-déposer (issues #265 lot 6, #189).
 * Remplace `js/view/rank/DragDropPanel.js`, le dernier écran qui n'existait
 * que dans l'admin ExtJS.
 *
 * Une colonne par division, plus « Non affectées » en tête. On déplace les
 * équipes d'une colonne à l'autre, le rang de départ est la position dans la
 * colonne, et l'enregistrement écrit tout d'un coup.
 *
 * Sémantique reprise telle quelle de l'écran ExtJS :
 * - une équipe posée dans une division est un `UPDATE` si elle avait déjà une
 *   ligne de classement (`id` renseigné), un `INSERT` sinon
 *   (`rank/updateRanksBatch` distingue les deux) ;
 * - une équipe **ramenée dans « Non affectées »** voit sa ligne de classement
 *   **supprimée** (`rank/removeFromDivision`), un appel par équipe ;
 * - une colonne de division ne se ferme que si elle est vide.
 *
 * Le glisser-déposer natif ne fonctionne pas au doigt. Comme cet écran doit
 * rester utilisable sur mobile — ce que l'admin ExtJS n'était pas du tout — on
 * double le geste : toucher une équipe la sélectionne, toucher une colonne l'y
 * déplace.
 */
export default {
    template: `
      <div class="p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <h1 class="text-2xl font-bold">Divisions / poules — réorganisation</h1>
          <div class="flex flex-wrap gap-2">
            <button class="btn btn-primary btn-sm" :disabled="!dirty || isSaving" @click="save">
              <span v-if="isSaving" class="loading loading-spinner loading-xs"></span>
              <i v-else class="fas fa-save"></i>
              Enregistrer
            </button>
            <button class="btn btn-ghost btn-sm" :disabled="!code_competition || isLoading" @click="load">
              <i class="fas fa-rotate"></i> Recharger
            </button>
          </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 mb-4">
          <label class="form-control">
            <span class="label-text mb-1">Compétition</span>
            <select v-model="code_competition" class="select select-bordered select-sm" @change="load">
              <option value="">—</option>
              <option v-for="c in competitions" :key="c.code_competition" :value="c.code_competition">
                {{ c.libelle }}
              </option>
            </select>
          </label>
          <button class="btn btn-outline btn-sm" :disabled="!code_competition" @click="addDivision">
            <i class="fas fa-plus"></i> Nouvelle division
          </button>
          <span v-if="dirty" class="badge badge-warning">modifications non enregistrées</span>
        </div>

        <div v-if="picked" class="alert alert-info mb-3 py-2">
          <i class="fas fa-hand-pointer"></i>
          <span>
            <strong>{{ picked.team.nom_equipe }}</strong> sélectionnée —
            touchez une colonne pour l'y déplacer.
          </span>
          <button class="btn btn-ghost btn-xs" @click="picked = null">Annuler</button>
        </div>

        <div v-if="!code_competition" class="alert">
          <i class="fas fa-circle-info"></i>
          <span>Choisissez une compétition pour afficher ses divisions.</span>
        </div>

        <div v-else-if="isLoading" class="flex items-center gap-3 py-8">
          <span class="loading loading-spinner loading-lg text-primary"></span>
          <span>Chargement des divisions…</span>
        </div>

        <div v-else class="flex gap-3 overflow-x-auto pb-2">
          <div v-for="col in columns"
               :key="col.key"
               class="card border shrink-0 w-64"
               :class="[
                 col.key === 'unassigned' ? 'bg-warning/10 border-warning/40' : 'bg-base-200 border-base-300',
                 dragOver === col.key ? 'ring-2 ring-primary' : '',
               ]"
               @dragover.prevent="dragOver = col.key"
               @dragleave="dragOver = null"
               @drop.prevent="drop(col, null)"
               @click="moveHere(col)">

            <div class="card-body p-3 gap-2">
              <div class="flex items-center justify-between">
                <span class="font-bold text-sm">
                  {{ col.key === 'unassigned' ? 'Non affectées' : 'Division ' + col.key }}
                  <span class="font-normal text-base-content/60">({{ col.teams.length }})</span>
                </span>
                <button v-if="col.key !== 'unassigned'"
                        class="btn btn-ghost btn-xs"
                        :title="col.teams.length ? 'Vider la division avant de la supprimer' : 'Supprimer cette division'"
                        @click.stop="removeDivision(col)">
                  <i class="fas fa-xmark"></i>
                </button>
              </div>

              <ul class="space-y-1 min-h-16">
                <li v-for="(team, index) in col.teams"
                    :key="team.id_equipe"
                    draggable="true"
                    class="flex items-center gap-2 rounded bg-base-100 border border-base-300 px-2 py-1 text-sm cursor-move"
                    :class="picked && picked.team.id_equipe === team.id_equipe ? 'ring-2 ring-primary' : ''"
                    :title="team.club"
                    @dragstart="dragStart(col, team)"
                    @dragover.prevent.stop="dragOver = col.key"
                    @drop.prevent.stop="drop(col, index)"
                    @click.stop="pick(col, team)">
                  <span class="badge badge-ghost badge-sm shrink-0">{{ index + 1 }}</span>
                  <span class="truncate">{{ team.nom_equipe }}</span>
                </li>
              </ul>

              <p v-if="col.teams.length === 0" class="text-xs text-base-content/50 italic">
                Déposez des équipes ici
              </p>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            competitions: [],
            code_competition: '',
            columns: [],
            dirty: false,
            isLoading: false,
            isSaving: false,
            dragged: null,
            dragOver: null,
            picked: null,
        };
    },
    created() {
        axios.get('/rest/action.php/competition/getCompetitions')
            .then(({ data }) => { this.competitions = data; })
            .catch(() => { this.competitions = []; });
    },
    methods: {
        load() {
            if (!this.code_competition) {
                this.columns = [];
                return;
            }
            this.isLoading = true;
            this.dirty = false;
            this.picked = null;
            const params = { code_competition: this.code_competition };
            Promise.all([
                axios.get('/rest/action.php/rank/getRanksByCompetitionGroupedByDivision', { params }),
                axios.get('/rest/action.php/rank/getUnassignedTeams', { params }),
            ])
                .then(([grouped, unassigned]) => {
                    const divisions = grouped.data || {};
                    this.columns = [
                        {
                            key: 'unassigned',
                            teams: (unassigned.data || []).map((t) => ({
                                id: null,
                                id_equipe: t.id_equipe,
                                nom_equipe: t.nom_equipe,
                                club: t.club,
                            })),
                        },
                        // Tri numérique : sans lui « 10 » passerait avant « 2 ».
                        ...Object.keys(divisions)
                            .sort((a, b) => (parseInt(a, 10) || 999) - (parseInt(b, 10) || 999))
                            .map((key) => ({
                                key,
                                teams: (divisions[key] || []).map((t) => ({
                                    id: t.id,
                                    id_equipe: t.id_equipe,
                                    nom_equipe: t.nom_equipe,
                                    club: t.club,
                                })),
                            })),
                    ];
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isLoading = false; });
        },
        dragStart(col, team) {
            this.dragged = { col, team };
        },
        drop(targetCol, index) {
            this.dragOver = null;
            const source = this.dragged;
            this.dragged = null;
            if (source) {
                this.move(source.col, source.team, targetCol, index);
            }
        },
        /** Sélection au doigt : premier appui sur l'équipe, second sur la colonne. */
        pick(col, team) {
            this.picked = (this.picked && this.picked.team.id_equipe === team.id_equipe)
                ? null
                : { col, team };
        },
        moveHere(targetCol) {
            if (!this.picked) {
                return;
            }
            const { col, team } = this.picked;
            this.picked = null;
            this.move(col, team, targetCol, null);
        },
        move(fromCol, team, toCol, index) {
            const i = fromCol.teams.findIndex((t) => t.id_equipe === team.id_equipe);
            if (i === -1) {
                return;
            }
            if (fromCol === toCol && index === null) {
                return;
            }
            fromCol.teams.splice(i, 1);
            // Retirer avant d'insérer décale l'index cible dans la même colonne.
            let cible = index;
            if (cible === null || cible > toCol.teams.length) {
                cible = toCol.teams.length;
            } else if (fromCol === toCol && i < cible) {
                cible -= 1;
            }
            toCol.teams.splice(cible, 0, team);
            this.dirty = true;
        },
        addDivision() {
            const max = this.columns
                .filter((c) => c.key !== 'unassigned')
                .reduce((m, c) => Math.max(m, parseInt(c.key, 10) || 0), 0);
            this.columns.push({ key: String(max + 1), teams: [] });
        },
        removeDivision(col) {
            if (col.teams.length > 0) {
                window.alert("Videz la division avant de la supprimer : déplacez d'abord ses équipes.");
                return;
            }
            this.columns = this.columns.filter((c) => c !== col);
        },
        save() {
            const updates = [];
            const removals = [];
            const removedNames = [];
            for (const col of this.columns) {
                if (col.key === 'unassigned') {
                    // Une équipe ramenée ici avec un id de classement existant
                    // perd sa ligne : c'est ce que fait l'écran ExtJS.
                    for (const team of col.teams) {
                        if (team.id && !Number.isNaN(Number(team.id))) {
                            removals.push(team.id);
                            removedNames.push(team.nom_equipe);
                        }
                    }
                    continue;
                }
                col.teams.forEach((team, index) => {
                    updates.push({
                        id: team.id,
                        id_equipe: team.id_equipe,
                        division: col.key,
                        rank_start: index + 1,
                    });
                });
            }
            if (updates.length === 0 && removals.length === 0) {
                window.alert('Aucune modification à enregistrer.');
                return;
            }
            // Sortir une équipe de sa division SUPPRIME sa ligne de classement,
            // points de départ compris. L'écran ExtJS le faisait sans rien
            // demander : on nomme les équipes concernées avant d'écrire.
            if (removals.length > 0) {
                const message = removals.length + ' équipe(s) vont être RETIRÉES du classement :\n\n'
                    + removedNames.map((n) => '  • ' + n).join('\n')
                    + '\n\nLeur ligne de classement est supprimée, rang de départ compris. Continuer ?';
                if (!window.confirm(message)) {
                    return;
                }
            }

            this.isSaving = true;
            const batch = () => {
                if (updates.length === 0) {
                    return Promise.resolve(null);
                }
                const formData = new FormData();
                formData.append('code_competition', this.code_competition);
                formData.append('updates', JSON.stringify(updates));
                return axios.post('/rest/action.php/rank/updateRanksBatch', formData);
            };
            // Les suppressions sont unitaires : on les enchaîne pour qu'une
            // erreur n'en laisse pas d'autres en vol.
            const purge = () => removals.reduce((chain, id) => chain.then(() => {
                const formData = new FormData();
                formData.append('id', id);
                return axios.post('/rest/action.php/rank/removeFromDivision', formData);
            }), Promise.resolve());

            batch()
                .then((response) => purge().then(() => response))
                .then((response) => {
                    onSuccess(this, response || { data: { message: 'Classements mis à jour' } });
                    this.load();
                })
                .catch((error) => onError(this, error))
                .finally(() => { this.isSaving = false; });
        },
    },
};
