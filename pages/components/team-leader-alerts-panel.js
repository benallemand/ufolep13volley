export default {
    template: `
      <div v-if="alerts.length > 0" class="bg-base-200 border border-2 border-base-300 p-4">
        <div v-for="alert in alerts" :key="alert.issue" role="alert" class="alert alert-error">
          <span>Attention ! {{ alert.issue }}</span>
        </div>
      </div>
    `,
    data() {
        return {
            alerts: [],
        };
    },
    methods: {
        fetchAlerts() {
            axios
                .get("/rest/action.php/alerts/getAlerts")
                .then((response) => {
                    this.alerts = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des alertes :", error);
                });
        },
    },
    created() {
        this.fetchAlerts();
    },
};