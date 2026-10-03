/**
 * Import groupé des licences liguasso (issue #394).
 *
 * Liguasso ne produit qu'une licence par PDF : un club en a des dizaines à
 * importer. On les dépose tous d'un coup (glisser-déposer, ou sélection
 * multiple, qui marche aussi sur téléphone), et chaque fichier part dans SA
 * requête, trois en parallèle. Un envoi unique de 100 PDF buterait sur les
 * limites PHP du mutualisé (`max_file_uploads`, `post_max_size`,
 * `max_execution_time`), et une erreur emporterait tout le lot.
 *
 * Le serveur rend un compte rendu par licence (`report`) : créée, mise à jour,
 * ou écartée avec son motif (#404), et si la licence portait une photo. Une
 * licence sans photo n'est pas bloquante : la photo n'est exigée que sur la
 * feuille de match (#343).
 *
 * Partagé par l'effectif du responsable et l'écran Joueurs de
 * l'administration. Émet `imported` à la fermeture si au moins un joueur a été
 * créé ou mis à jour, pour que l'écran se rafraîchisse.
 */
const CONCURRENCY = 3;

export default {
    emits: ['close', 'imported'],
    template: `
      <dialog class="modal modal-open" data-testid="licence-import">
        <div class="modal-box max-w-2xl">
          <h3 class="font-bold text-lg mb-1">Importer des licences</h3>
          <p class="text-sm text-base-content/70 mb-3">
            Liguasso produit un PDF par licence : déposez-les tous d'un coup. Les
            joueurs existants sont mis à jour (licence, homologation, photo), les
            autres sont créés.
          </p>

          <div class="border-2 border-dashed rounded-box p-5 text-center transition"
               :class="dragging ? 'border-primary bg-primary/5' : 'border-base-300'"
               data-testid="licence-import-dropzone"
               @dragover.prevent="dragging = true"
               @dragleave.prevent="dragging = false"
               @drop.prevent="onDrop">
            <i class="fas fa-file-pdf text-3xl text-base-content/40"></i>
            <p class="hidden sm:block mt-1">Glissez vos fichiers PDF ici</p>
            <label class="btn btn-primary btn-sm mt-2" :class="{ 'btn-disabled': running }">
              <i class="fas fa-folder-open"></i> Choisir des fichiers
              <input type="file"
                     accept="application/pdf,.pdf"
                     multiple
                     class="hidden"
                     data-testid="licence-import-input"
                     :disabled="running"
                     @change="onPick"/>
            </label>
            <p class="text-xs text-base-content/60 mt-2">
              Sur ordinateur : dans le dossier des téléchargements, sélectionnez tous les
              PDF (Ctrl+A). Sur téléphone : sélectionnez plusieurs fichiers d'un coup.
            </p>
            <p v-if="ignored" class="text-xs text-warning mt-1" data-testid="licence-import-ignored">
              {{ ignored }} fichier(s) ignoré(s) : ce ne sont pas des PDF.
            </p>
          </div>

          <div v-if="files.length" class="mt-4">
            <div class="flex items-center gap-3">
              <progress class="progress progress-primary flex-1"
                        :value="finishedCount" :max="files.length"></progress>
              <span class="text-sm tabular-nums" data-testid="licence-import-progress">
                {{ finishedCount }} / {{ files.length }}
              </span>
            </div>

            <div v-if="finished" class="alert mt-3 py-2 text-sm"
                 :class="stats.rejected || stats.errors ? 'alert-warning' : 'alert-success'"
                 data-testid="licence-import-summary">
              <span>
                {{ stats.updated }} mise(s) à jour, {{ stats.created }} création(s)<span
                  v-if="stats.rejected">, {{ stats.rejected }} licence(s) écartée(s)</span><span
                  v-if="stats.errors">, {{ stats.errors }} fichier(s) en erreur</span>.
                <span v-if="stats.withoutPhoto">
                  {{ stats.withoutPhoto }} sans photo : à ajouter avant d'inscrire le joueur sur une feuille de match.
                </span>
              </span>
            </div>

            <ul class="mt-3 max-h-64 overflow-auto divide-y divide-base-200 text-sm">
              <li v-for="item in files" :key="item.key" class="py-1.5"
                  :data-testid="'licence-import-file-' + item.state">
                <div class="flex items-center gap-2">
                  <i class="fas fa-file-pdf text-base-content/40"></i>
                  <span class="truncate flex-1" :title="item.name">{{ item.name }}</span>
                  <span v-if="item.state === 'running'" class="loading loading-spinner loading-xs"></span>
                  <span class="badge badge-sm shrink-0" :class="badgeClass(item)">{{ badgeLabel(item) }}</span>
                </div>
                <p v-if="item.state === 'error'" class="text-xs text-error ml-6">{{ item.message }}</p>
                <ul v-if="item.report.length" class="ml-6 text-xs">
                  <li v-for="(line, i) in item.report" :key="i"
                      :class="line.status === 'rejected' ? 'text-warning' : 'text-base-content/70'">
                    {{ line.joueur }} :
                    <template v-if="line.status === 'rejected'">écartée, {{ line.message }}</template>
                    <template v-else>{{ line.status === 'created' ? 'créé' : 'mis à jour' }}<span
                        v-if="!line.photo">, sans photo</span></template>
                  </li>
                </ul>
              </li>
            </ul>
          </div>

          <div class="modal-action">
            <button v-if="finished && stats.errors" class="btn btn-ghost btn-sm" @click="retryErrors">
              <i class="fas fa-rotate-right"></i> Réessayer les fichiers en erreur
            </button>
            <button class="btn btn-ghost btn-sm" :disabled="running" @click="close">Fermer</button>
            <button class="btn btn-primary btn-sm"
                    data-testid="licence-import-submit"
                    :disabled="running || !pendingCount"
                    @click="run">
              <span v-if="running" class="loading loading-spinner loading-xs"></span>
              <i v-else class="fas fa-file-import"></i>
              Importer {{ pendingCount || '' }} fichier(s)
            </button>
          </div>
        </div>
        <div class="modal-backdrop" @click="close"></div>
      </dialog>
    `,
    data() {
        return {
            files: [],
            dragging: false,
            running: false,
            ignored: 0,
            nextKey: 0,
        };
    },
    computed: {
        pendingCount() {
            return this.files.filter((f) => f.state === 'pending').length;
        },
        finishedCount() {
            return this.files.filter((f) => f.state === 'done' || f.state === 'error').length;
        },
        finished() {
            return this.files.length > 0 && !this.running && this.pendingCount === 0;
        },
        stats() {
            const lines = this.files.flatMap((f) => f.report);
            const imported = lines.filter((l) => l.status !== 'rejected');
            return {
                created: lines.filter((l) => l.status === 'created').length,
                updated: lines.filter((l) => l.status === 'updated').length,
                rejected: lines.length - imported.length,
                withoutPhoto: imported.filter((l) => !l.photo).length,
                errors: this.files.filter((f) => f.state === 'error').length,
            };
        },
    },
    methods: {
        onPick(event) {
            this.add(event.target.files);
            // Permet de re-choisir les mêmes fichiers.
            event.target.value = '';
        },
        onDrop(event) {
            this.dragging = false;
            if (!this.running) {
                this.add(event.dataTransfer.files);
            }
        },
        /** Ajoute les PDF, sans doublon (même nom, même taille). */
        add(fileList) {
            for (const file of Array.from(fileList || [])) {
                const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
                if (!isPdf) {
                    this.ignored += 1;
                    continue;
                }
                if (this.files.some((f) => f.name === file.name && f.file.size === file.size)) {
                    continue;
                }
                this.files.push({
                    key: this.nextKey++,
                    file,
                    name: file.name,
                    state: 'pending',
                    report: [],
                    message: '',
                });
            }
        },
        /** Un fichier par requête, `CONCURRENCY` en vol. */
        run() {
            const queue = this.files.filter((f) => f.state === 'pending');
            if (!queue.length) {
                return Promise.resolve();
            }
            this.running = true;
            const worker = () => {
                const item = queue.shift();
                if (!item) {
                    return Promise.resolve();
                }
                return this.upload(item).then(worker);
            };
            return Promise.all(Array.from({ length: CONCURRENCY }, worker))
                .finally(() => { this.running = false; });
        },
        upload(item) {
            item.state = 'running';
            const formData = new FormData();
            formData.append('licences', item.file);
            return axios.post('/rest/action.php/player/update_from_licence_file', formData)
                .then(({ data }) => {
                    item.report = (data && data.report) || [];
                    item.state = 'done';
                })
                .catch((error) => {
                    item.message = (error.response && error.response.data && error.response.data.message)
                        || "Le serveur n'a pas répondu";
                    item.state = 'error';
                });
        },
        retryErrors() {
            for (const item of this.files) {
                if (item.state === 'error') {
                    item.state = 'pending';
                    item.message = '';
                }
            }
            this.run();
        },
        badgeLabel(item) {
            if (item.state === 'pending') return 'en attente';
            if (item.state === 'running') return 'import…';
            if (item.state === 'error') return 'erreur';
            return item.report.some((l) => l.status === 'rejected') ? 'à vérifier' : 'importé';
        },
        badgeClass(item) {
            if (item.state === 'error') return 'badge-error';
            if (item.state !== 'done') return 'badge-ghost';
            return item.report.some((l) => l.status === 'rejected') ? 'badge-warning' : 'badge-success';
        },
        close() {
            if (this.running) {
                return;
            }
            if (this.stats.created + this.stats.updated > 0) {
                this.$emit('imported');
            }
            this.$emit('close');
        },
    },
};
