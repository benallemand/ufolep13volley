export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th>utilisateur</th>
            <th>equipe</th>
            <th>date</th>
            <th>details</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="activity in sorted" :key="activity.id">
            <td>{{ activity.utilisateur }}</td>
            <td>{{ activity.nom_equipe }}</td>
            <td>{{ activity.date }}</td>
            <td>{{ activity.description }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            response: [],
            // `my_team` : l'historique de l'équipe courante, même pour un admin
            // qui en est responsable (#419).
            fetchUrl: "/rest/action.php/activity/getActivity?my_team=1"
        };
    },
    computed: {
        sorted() {
            return this.response;
        }
    },
    methods: {
        fetch() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.response = response.data;
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