export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <div v-for="info in infos" :key="info.description" class="flex justify-start">
          <div class="card w-96 shadow-xl">
            <a href="https://chat.whatsapp.com/Hk08bFipoTMDL0dUYtQLo9" target="_blank">
              <figure>
                <img
                    :src="info.img_src"
                    alt=""/>
              </figure>
            </a>
            <div class="card-body">
              <h2 class="card-title">Se tenir informé</h2>
              <p>{{ info.description }}</p>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {
            infos: [],
        };
    },
    methods: {
        fetchInfos() {
            axios
                .get("/rest/action.php/alerts/getInfos")
                .then((response) => {
                    this.infos = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des infos :", error);
                });
        },
    },
    created() {
        this.fetchInfos();
    },
};