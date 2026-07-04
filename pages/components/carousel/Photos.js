export default {
    template: `
      <div v-if="items.photos.length" class="flex flex-col items-center gap-2 w-full max-w-5xl">
        <div class="relative w-full">
          <div ref="track"
               class="flex gap-2 overflow-x-auto snap-x snap-mandatory scroll-smooth rounded-box"
               style="scrollbar-width: none">
            <div v-for="(item, index) in items.photos" :key="item.id"
                 class="snap-start shrink-0 w-full sm:w-[calc((100%-0.5rem)/2)] lg:w-[calc((100%-1rem)/3)] cursor-zoom-in"
                 @click="openLightbox(index)">
              <img :src="item.src" loading="lazy"
                   class="h-56 w-full object-cover rounded-box hover:opacity-80 transition-opacity"/>
            </div>
          </div>
          <button class="btn btn-circle btn-sm absolute left-2 top-1/2 -translate-y-1/2 shadow"
                  aria-label="Photos précédentes"
                  @click="scroll(-1)">❮
          </button>
          <button class="btn btn-circle btn-sm absolute right-2 top-1/2 -translate-y-1/2 shadow"
                  aria-label="Photos suivantes"
                  @click="scroll(1)">❯
          </button>
        </div>
        <div v-if="items.more_link">
          d'autres photos dispos <a class="link" target="_blank"
                                    :href="items.more_link">ici</a> !
        </div>
        <div v-if="lightboxIndex !== null"
             class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4"
             @click.self="closeLightbox">
          <img :src="items.photos[lightboxIndex].src"
               class="max-h-full max-w-full rounded-box shadow-lg"/>
          <button class="btn btn-circle absolute left-4 top-1/2 -translate-y-1/2"
                  aria-label="Photo précédente"
                  @click="moveLightbox(-1)">❮
          </button>
          <button class="btn btn-circle absolute right-4 top-1/2 -translate-y-1/2"
                  aria-label="Photo suivante"
                  @click="moveLightbox(1)">❯
          </button>
          <button class="btn btn-circle absolute right-4 top-4"
                  aria-label="Fermer"
                  @click="closeLightbox">✕
          </button>
        </div>
      </div>
    `,
    data() {
        return {
            items: {photos: [], more_link: ''},
            lightboxIndex: null,
            fetchUrl: "/ajax/getVolleyballImages.php"
        };
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.items = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement:", error);
                });
        },
        scroll(direction) {
            const track = this.$refs.track;
            track.scrollBy({left: direction * track.clientWidth, behavior: 'smooth'});
        },
        openLightbox(index) {
            this.lightboxIndex = index;
        },
        closeLightbox() {
            this.lightboxIndex = null;
        },
        moveLightbox(direction) {
            const count = this.items.photos.length;
            this.lightboxIndex = (this.lightboxIndex + direction + count) % count;
        },
        onKeydown(event) {
            if (this.lightboxIndex === null) return;
            if (event.key === 'Escape') this.closeLightbox();
            if (event.key === 'ArrowLeft') this.moveLightbox(-1);
            if (event.key === 'ArrowRight') this.moveLightbox(1);
        },
    },
    created() {
        this.fetch();
    },
    mounted() {
        document.addEventListener('keydown', this.onKeydown);
    },
    unmounted() {
        document.removeEventListener('keydown', this.onKeydown);
    },
};
