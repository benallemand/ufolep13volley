export default {
    template: `
      <div class="bg-base-100">
        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th>titre</th>
            <th>texte</th>
            <th>lien</th>
            <th>date</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }}</td>
            <td v-html="item.text"></td>
            <td><a class="link" v-if="item.file_path.length > 0" :href="item.file_path" target="_blank">lire...</a></td>
            <td>{{ item.news_date }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            items: [],
            fetchUrl: "/rest/action.php/news/getLastNews"
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