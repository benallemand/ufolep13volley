/**
 * Un règlement, lu dans le dossier Google Drive de la commission (issue #342).
 *
 * Le serveur (`rules/getRules`) récupère le document, le met en cache et le
 * reconstruit à partir d'une liste blanche de balises : le HTML des articles
 * est donc sûr à injecter. Les titres, eux, restent du texte (interpolation).
 * Modifier le document suffit : le site le relit au plus tard 15 minutes après.
 */
export default {
    props: {
        slug: {type: String, default: 'general'},
    },
    template: `
      <div class="container mx-auto p-4" data-testid="rules-document" :data-slug="slug">
        <div class="bg-base-100 shadow-xl rounded-lg p-4 mb-6">
          <h1 class="text-3xl font-bold text-center">{{ title }}</h1>
          <h2 v-if="slug === 'general'" class="text-xl text-center mt-2">Chaque équipe doit l'appliquer et posséder
            un exemplaire récent du règlement de la Fédération Française de Volley Ball (F.F.V.B.)</h2>
          <p v-if="season" class="text-center mt-2">
            <span class="badge badge-primary badge-outline" data-testid="rules-season">Saison {{ season }}</span>
          </p>
          <p v-if="fetchedAt" class="text-sm text-center mt-2 text-base-content/70" data-testid="rules-fetched-at">
            Document officiel de la commission, relu le {{ fetchedAt }}
          </p>
          <div v-if="pdfUrl" class="flex justify-center mt-3">
            <a :href="pdfUrl" target="_blank" rel="noopener" class="btn btn-sm btn-outline gap-2"
               data-testid="rules-original">
              <i class="fas fa-file-pdf"></i> Voir / télécharger le document original
            </a>
          </div>
        </div>

        <div v-if="loading" class="flex justify-center p-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <div v-else-if="!['ok', 'stale'].includes(status)" role="alert" class="alert alert-warning mb-6"
             data-testid="rules-unavailable">
          <i class="fas fa-triangle-exclamation"></i>
          <span>Ce règlement est momentanément indisponible. Merci de réessayer un peu plus tard, ou de contacter
            la commission.</span>
        </div>

        <template v-else>
          <div v-if="status === 'stale'" role="alert" class="alert alert-info mb-6" data-testid="rules-stale">
            <i class="fas fa-circle-info"></i>
            <span>Le document officiel n'a pas pu être relu : voici sa dernière version connue, du {{ fetchedAt }}.</span>
          </div>

          <div v-if="introHtml" class="bg-base-200 rounded-xl p-4 mb-6 [&_p]:mb-2 [&_ul]:list-disc [&_ul]:pl-5"
               v-html="introHtml"></div>

          <div class="overflow-x-auto mb-6">
            <table class="table w-full bg-base-100 border border-base-300" data-testid="rules-toc">
              <thead>
              <tr>
                <th>Récapitulatif</th>
                <th>Article</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="article in summary" :key="article.anchor">
                <td>{{ article.title }}</td>
                <td>
                  <a :href="'#' + article.anchor" class="link link-primary"
                     @click.prevent.stop="scrollTo(article.anchor)">{{ article.number }}</a>
                </td>
              </tr>
              </tbody>
            </table>
          </div>

          <!-- min(…, 100%) : sur un téléphone, une carte de 320 px débordait -->
          <div class="grid grid-cols-[repeat(auto-fit,minmax(min(320px,100%),1fr))] gap-4">
            <div v-for="article in articles" :key="article.anchor" :id="article.anchor"
                 class="bg-base-200 rounded-xl p-4 scroll-mt-4" data-testid="rules-article">
              <h4 class="font-bold text-lg mb-2">Article {{ article.number }} : {{ article.title }}</h4>
              <div class="overflow-x-auto
                          [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:mb-2
                          [&_p]:mb-2 [&_h4]:font-semibold [&_h4]:mt-2 [&_h4]:mb-1
                          [&_table]:w-full [&_table]:mb-2 [&_td]:border [&_td]:border-base-300 [&_td]:p-2
                          [&_th]:border [&_th]:border-base-300 [&_th]:p-2 [&_td_p]:mb-0
                          [&_a]:link [&_a]:link-primary"
                   v-html="article.html"></div>
            </div>
          </div>
        </template>
      </div>
    `,
    data() {
        return {
            loading: true,
            status: null,
            label: null,
            season: null,
            fetchedAt: null,
            pdfUrl: null,
            introHtml: '',
            articles: [],
        };
    },
    computed: {
        title() {
            return this.label || (this.slug === 'general' ? 'Règlement général' : 'Règlement');
        },
        // Récapitulatif par ordre alphabétique des titres, comme avant #342.
        summary() {
            return [...this.articles].sort((a, b) => a.title.localeCompare(b.title, 'fr'));
        },
    },
    watch: {
        slug() {
            this.load();
        },
    },
    methods: {
        // Mode hash du routeur : un href="#article-N" changerait de route.
        scrollTo(anchor) {
            const target = this.$el.querySelector('#' + CSS.escape(anchor));
            if (target) target.scrollIntoView({behavior: 'smooth', block: 'start'});
        },
        async load() {
            this.loading = true;
            try {
                const {data} = await axios.get('/rest/action.php/rules/getRules', {params: {slug: this.slug}});
                this.status = data.status;
                this.label = data.label;
                this.season = data.season;
                this.fetchedAt = data.fetched_at;
                this.pdfUrl = data.pdf_url;
                this.introHtml = data.intro_html || '';
                this.articles = Array.isArray(data.articles) ? data.articles : [];
            } catch (error) {
                console.error('Erreur lors du chargement du règlement :', error);
                this.status = 'error';
                this.articles = [];
            } finally {
                this.loading = false;
            }
        },
    },
    created() {
        this.load();
    },
};
