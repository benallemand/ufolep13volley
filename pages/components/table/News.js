/**
 * Dernières nouvelles — liste mobile-first repliable (#230).
 *
 * Affiche uniquement le titre (+ date) de chaque nouvelle ; un tap sur le titre
 * déplie le détail (texte + lien éventuel). Remplace l'ancien tableau large peu
 * lisible sur smartphone. Endpoint inchangé : news/getLastNews.
 */
export default {
    template: `
      <section class="w-full max-w-2xl">
        <h2 class="text-2xl font-bold text-primary text-center mb-3">dernières nouvelles</h2>
        <div class="flex flex-col gap-2">
          <div v-for="item in items" :key="item.id"
               class="card bg-base-100 border border-base-300 shadow-sm">
            <button type="button"
                    class="flex items-center justify-between gap-2 w-full text-left p-4 min-h-[44px]"
                    :aria-expanded="isOpen(item.id)"
                    @click="toggle(item.id)">
              <span class="flex flex-col">
                <span class="font-semibold">{{ item.title }}</span>
                <span class="text-xs opacity-60">{{ item.news_date }}</span>
              </span>
              <i class="fas fa-chevron-down transition-transform shrink-0"
                 :class="{ 'rotate-180': isOpen(item.id) }"></i>
            </button>
            <div v-if="isOpen(item.id)" class="px-4 pb-4 border-t border-base-200 pt-3">
              <div class="prose max-w-none text-sm" v-html="item.text"></div>
              <a v-if="item.file_path && item.file_path.length > 0"
                 class="link link-primary inline-block mt-2 text-sm"
                 :href="item.file_path" target="_blank" rel="noopener">
                <i class="fas fa-up-right-from-square mr-1"></i>lire le document
              </a>
            </div>
          </div>
          <p v-if="items.length === 0" class="text-center opacity-60 text-sm py-4">
            Aucune nouvelle pour le moment.
          </p>
        </div>
      </section>
    `,
    data() {
        return {
            items: [],
            openIds: [],
            fetchUrl: "/rest/action.php/news/getLastNews"
        };
    },
    methods: {
        isOpen(id) {
            return this.openIds.includes(id);
        },
        toggle(id) {
            if (this.openIds.includes(id)) {
                this.openIds = this.openIds.filter((openId) => openId !== id);
            } else {
                this.openIds.push(id);
            }
        },
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.items = Array.isArray(response.data) ? response.data : [];
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement:", error);
                });
        },
    },
    created() {
        this.fetch();
    },
};
