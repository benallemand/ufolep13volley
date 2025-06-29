export default {
    template: `
      <div class="bg-base-100">
        <table class="table table-pin-rows">
          <thead>
          <tr>
            <th>compétition</th>
            <th>année</th>
            <th>demi-saison</th>
            <th>division</th>
            <th>champion</th>
            <th>vice-champion</th>
            <th>diplômes</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in items" :key="item.period+item.league+item.division+item.demi_saison">
            <td>{{ item.league }}</td>
            <td>{{ item.period }}</td>
            <td>{{ item.demi_saison }}</td>
            <td>{{ item.division }}</td>
            <td>{{ item.champion }}</td>
            <td>{{ item.vice_champion }}</td>
            <td><a class="btn" :href="'/rest/action.php/halloffame/download_diploma?ids='+item.ids"
                   target="_blank">Télécharger</a></td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            items: [],
            fetchUrl: "/rest/action.php/halloffame/getHallOfFameDisplay"
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