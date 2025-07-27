export default {
    template: `
      <div class="bg-base-100">
        <div class="text-center mt-4">
          <div class="text-2xl font-bold text-primary">sites web des clubs</div>
        </div>
        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th>club</th>
            <th>site</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.nom_club }}</td>
            <td>
              <a class="link"
                 v-if="item.web_site.length > 0 && !['null', 'undefined'].includes(item.web_site) && item.web_site.includes('http')"
                 :href="item.web_site" target="_blank">{{ item.web_site }}
              </a>
              <span v-if="item.web_site.length > 0 && !['null', 'undefined'].includes(item.web_site) && !item.web_site.includes('http')">{{ item.web_site }}
              </span>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            items: [],
            fetchUrl: "/rest/action.php/team/getWebSites"
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