/**
 * Bilan annuel d'activités (issue #265, lot 5).
 * Remplace `js/view/bilan/Form.js` et ses deux actions dans `Administration.js`.
 *
 * Deux temps, comme l'écran ExtJS :
 * 1. `bilan/getBilanData?saison=` extrait les chiffres de la saison, affichés
 *    en lecture seule ;
 * 2. le PDF est produit par `bilanPdf.php`, qui **recalcule les chiffres côté
 *    serveur** à partir de la seule saison — seuls les commentaires libres
 *    viennent du formulaire, ils ne sont donc pas falsifiables.
 *
 * Le téléchargement passe par une vraie soumission de formulaire et non par
 * axios : `bilanPdf.php` répond un PDF en pièce jointe, que le navigateur doit
 * recevoir comme une navigation pour l'enregistrer.
 */
export default {
    template: `
      <div class="p-4 max-w-4xl">
        <h1 class="text-2xl font-bold mb-4">Bilan annuel</h1>

        <div class="card bg-base-200 mb-4">
          <div class="card-body p-4">
            <div class="flex flex-wrap items-end gap-3">
              <div class="form-control">
                <label class="label py-1" for="bilan-saison">
                  <span class="label-text">Saison <span class="text-error">*</span></span>
                </label>
                <input id="bilan-saison"
                       v-model.trim="saison"
                       type="text"
                       class="input input-bordered"
                       placeholder="2025-2026"
                       pattern="\\d{4}-\\d{4}"/>
              </div>
              <button class="btn btn-primary" :disabled="!saisonValide || isLoading" @click="load">
                <span v-if="isLoading" class="loading loading-spinner loading-xs"></span>
                <i v-else class="fas fa-download"></i>
                Charger les chiffres
              </button>
            </div>
            <p v-if="saison && !saisonValide" class="text-error text-sm mt-2">
              Format attendu : AAAA-AAAA (ex. 2025-2026)
            </p>
          </div>
        </div>

        <div v-if="error" class="alert alert-error mb-4">
          <i class="fas fa-triangle-exclamation"></i><span>{{ error }}</span>
        </div>

        <div v-if="data" class="card border border-base-300 mb-4">
          <div class="card-body p-4">
            <h2 class="card-title text-base">Chiffres extraits de la base</h2>

            <div class="overflow-x-auto">
              <table class="table table-sm w-auto">
                <thead>
                  <tr><th>Compétition</th><th class="text-right">Nombre de matchs</th></tr>
                </thead>
                <tbody>
                  <tr v-for="m in data.matchs" :key="m.competition">
                    <td>{{ m.competition }}</td>
                    <td class="text-right">{{ m.nb_matchs }}</td>
                  </tr>
                  <tr class="font-bold">
                    <td>TOTAL</td>
                    <td class="text-right">{{ data.total_matchs }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="stats stats-vertical sm:stats-horizontal shadow mt-3">
              <div class="stat py-3">
                <div class="stat-title text-xs">Clubs participants</div>
                <div class="stat-value text-2xl">{{ data.nb_clubs }}</div>
              </div>
              <div class="stat py-3">
                <div class="stat-title text-xs">Licenciés participants</div>
                <div class="stat-value text-2xl">{{ data.nb_licencies }}</div>
              </div>
              <div class="stat py-3">
                <div class="stat-title text-xs">Équipes récompensées</div>
                <div class="stat-value text-2xl">{{ data.nb_recompenses }}</div>
              </div>
              <div class="stat py-3">
                <div class="stat-title text-xs">Coupes décernées</div>
                <div class="stat-value text-2xl">{{ data.nb_coupes }}</div>
              </div>
            </div>

            <ul v-if="data.coupes && data.coupes.length" class="list-disc ml-6 mt-3 text-sm">
              <li v-for="c in data.coupes" :key="c.recompense">
                {{ c.recompense }} : <strong>{{ c.vainqueur }}</strong>
              </li>
            </ul>
          </div>
        </div>

        <form ref="pdfForm" method="post" action="/bilanPdf.php" target="_blank" class="card border border-base-300">
          <div class="card-body p-4">
            <h2 class="card-title text-base">Commentaires à compléter</h2>
            <input type="hidden" name="saison" :value="saison"/>

            <div class="form-control">
              <label class="label py-1"><span class="label-text">Responsable de la commission</span></label>
              <input v-model="form.responsable" name="responsable" type="text" class="input input-bordered"/>
            </div>

            <div v-for="champ in champsTexte" :key="champ.name" class="form-control">
              <label class="label py-1"><span class="label-text">{{ champ.label }}</span></label>
              <textarea v-model="form[champ.name]"
                        :name="champ.name"
                        class="textarea textarea-bordered"
                        :rows="champ.rows"></textarea>
            </div>

            <div class="card-actions justify-end mt-2">
              <button type="submit" class="btn btn-primary" :disabled="!saisonValide">
                <i class="fas fa-file-pdf"></i> Télécharger le PDF
              </button>
            </div>
          </div>
        </form>
      </div>
    `,
    data() {
        return {
            // Une saison se termine en juin : par défaut on propose la dernière
            // saison terminée, celle dont le bilan est à faire.
            saison: (() => {
                const now = new Date();
                const fin = (now.getMonth() + 1) >= 7 ? now.getFullYear() : now.getFullYear() - 1;
                return `${fin - 1}-${fin}`;
            })(),
            data: null,
            error: null,
            isLoading: false,
            champsTexte: [
                { name: 'types_public', label: 'Types de public participant', rows: 3 },
                { name: 'formations', label: 'Formations effectuées', rows: 2 },
                { name: 'reunions', label: 'Réunions statutaires / participation', rows: 3 },
                { name: 'impression_generale', label: 'Impression générale sur la saison / besoins', rows: 4 },
                { name: 'coupe_nationale', label: 'Coupe nationale', rows: 4 },
                { name: 'axes_amelioration', label: "Axes d'amélioration", rows: 4 },
            ],
            form: {
                responsable: '',
                // Valeurs par défaut reprises telles quelles du formulaire ExtJS
                types_public: "Adultes féminins et masculins\n"
                    + "Jeunes (16 ans + avec accord parental géré par le club d'affiliation)",
                formations: 'Néant',
                reunions: '',
                impression_generale: '',
                coupe_nationale: '',
                axes_amelioration: '',
            },
        };
    },
    computed: {
        saisonValide() {
            return /^\d{4}-\d{4}$/.test(this.saison);
        },
    },
    methods: {
        load() {
            this.isLoading = true;
            this.error = null;
            axios.get('/rest/action.php/bilan/getBilanData', { params: { saison: this.saison } })
                .then(({ data }) => { this.data = data; })
                .catch((e) => {
                    this.data = null;
                    this.error = (e.response && e.response.data && e.response.data.message)
                        || "Impossible de charger les chiffres de la saison.";
                })
                .finally(() => { this.isLoading = false; });
        },
    },
};
