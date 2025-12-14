export default {
    template: `
      <div v-if="toScheduleMatchs.length > 0">
        <h3 class="text-lg font-bold mt-4">Matchs à planifier</h3>
        <table class="table mt-2 table-pin-rows bg-base-100">
          <thead>
          <tr>
            <th>date</th>
            <th>match</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="match in toScheduleMatchs" :key="match.registry_key">
            <td><span class="ml-1 badge badge-warning">date et lieu à déterminer</span></td>
            <td>{{ match.registry_value }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    `, props: {
        fetchUrl: {
            type: String, required: true,
        }
    }, data() {
        return {
            toScheduleMatchs: [],
        };
    }, computed: {
    }, methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.toScheduleMatchs = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des matchs :", error);
                });
        },
    }, created() {
        this.fetch();
    },
};
