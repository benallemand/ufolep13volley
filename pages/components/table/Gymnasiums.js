export default {
    template: `
      <div class="bg-base-100">
        <div class="text-center mt-4">
          <div class="text-2xl font-bold text-primary">gymnases</div>
        </div>
        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th>ville</th>
            <th>nom</th>
            <th>adresse</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.ville }}</td>
            <td>
              <a class="link" :href="'https://www.google.com/maps/place/'+item.gps" target="_blank">{{ item.nom }}</a>
            </td>
            <td>{{ item.adresse }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            items: [],
            fetchUrl: "/rest/action.php/court/getGymnasiums"
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