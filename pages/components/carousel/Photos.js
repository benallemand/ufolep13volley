export default {
    template: `
      <div class="flex flex-col items-center">
        <div class="carousel carousel-vertical rounded-box h-96">
          <div v-for="item in items.photo" :key="item.id" class="carousel-item h-full">
            <img
                :src="'https://farm'+item.farm+'.staticflickr.com/'+item.server+'/'+item.id+'_'+item.secret+'.jpg'"/>
          </div>
        </div>
        <div>
          d'autres photos dispos <a class="link" target="_blank"
                                    href="https://www.flickr.com/photos/149988821@N04/albums/">ici</a> !
        </div>
      </div>
    `,
    data() {
        return {
            items: [],
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
    },
    created() {
        this.fetch();
    },
};