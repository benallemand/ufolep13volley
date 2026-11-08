import {onError, onSuccess} from "../../../toaster.js";

export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <table class="table">
          <thead>
          <tr>
            <th>{{ team.nom_equipe }}</th>
            <td><img :src="$url('/rest/action.php/photo/get_photo?path_photo='+team.path_photo)" class="max-h-64" alt=""/></td>
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
          <!-- Coupe Isoardi (issue #467) : l'équipe y est inscrite avec le
               championnat masculin, mais peut s'en retirer pendant la période. -->
          <tr v-if="cup.concerned" data-testid="isoardi-registration">
            <td>Coupe 6x6 Isoardi</td>
            <td>
              <div class="flex flex-wrap items-center gap-2">
                <span v-if="cup.is_cup_registered" class="badge badge-success" data-testid="isoardi-status">inscrite</span>
                <span v-else class="badge badge-warning" data-testid="isoardi-status">désinscrite</span>
                <template v-if="cup.is_open">
                  <button v-if="cup.is_cup_registered" class="btn btn-sm btn-warning" :disabled="saving"
                          data-testid="isoardi-toggle" @click="setCupRegistration(0)">
                    <i class="fas fa-right-from-bracket mr-1"></i>Désinscrire de la coupe Isoardi
                  </button>
                  <button v-else class="btn btn-sm btn-success" :disabled="saving"
                          data-testid="isoardi-toggle" @click="setCupRegistration(1)">
                    <i class="fas fa-right-to-bracket mr-1"></i>Réinscrire à la coupe Isoardi
                  </button>
                </template>
              </div>
              <p class="text-sm opacity-70 mt-1">
                <template v-if="cup.is_open">Choix modifiable jusqu'au {{ cup.limit_date }} inclus.</template>
                <template v-else-if="cup.limit_date">Choix clos le {{ cup.limit_date }} : contactez la commission.</template>
                <template v-else>Choix clos : contactez la commission.</template>
              </p>
            </td>
          </tr>
          <tr>
            <td colspan="2" class="text-center">
              <a :href="$url('/teamSheetPdf.php?id='+team.id_equipe)" target="_blank" role="button"
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
            cup: {concerned: false},
            saving: false,
        };
    },
    methods: {
        fetchCup() {
            axios
                .get("/rest/action.php/team/getMyCupRegistration")
                .then((response) => {
                    this.cup = response.data;
                })
                .catch(() => {
                    this.cup = {concerned: false};
                });
        },
        setCupRegistration(value) {
            const formData = new FormData();
            formData.append('is_cup_registered', value);
            this.saving = true;
            axios
                .post("/rest/action.php/team/setMyCupRegistration", formData)
                .then((response) => {
                    onSuccess(this, response);
                    this.fetchCup();
                })
                .catch((error) => {
                    onError(this, error);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
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
        this.fetchCup();
    },
};