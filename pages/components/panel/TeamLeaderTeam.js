export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <table class="table">
          <thead>
          <tr>
            <th>{{ team.nom_equipe }}</th>
            <td><img :src="'/rest/action.php/photo/get_photo?path_photo='+team.path_photo" class="max-h-64" alt=""/></td>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td>Club</td>
            <td>{{ team.club }}</td>
          </tr>
          <tr>
            <td>Responsable</td>
            <td>{{ team.responsable }}</td>
          </tr>
          <tr>
            <td>Téléphone 1</td>
            <td>{{ team.telephone_1 }}</td>
          </tr>
          <tr>
            <td>Téléphone 2</td>
            <td>{{ team.telephone_2 }}</td>
          </tr>
          <tr>
            <td>Email</td>
            <td>{{ team.email }}</td>
          </tr>
          <tr>
            <td>Créneaux</td>
            <td>{{ team.gymnasiums_list }}</td>
          </tr>
          <tr>
            <td>Site web</td>
            <td>{{ team.web_site }}</td>
          </tr>
          <tr>
            <td colspan="2" class="text-center">
              <a :href="'/teamSheetPdf.php?id='+team.id_equipe" target="_blank" role="button"
                               class="btn btn-info mr-2">Télécharger la fiche équipe</a>
              <router-link :to="'/team/edit'"
                           class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifier
              </router-link>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    `,
    data() {
        return {
            team: {},
        };
    },
    methods: {
        fetch() {
            axios
                .get("/rest/action.php/team/getMyTeam")
                .then((response) => {
                    this.team = response.data[0];
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement de l'équipe :", error);
                });
        },
    },
    created() {
        this.fetch();
    },
};