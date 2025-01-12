export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <li v-for="player in players" :key="player.id" class="card shadow-md bg-base-100">
            <div class="card-body">
              <div class="card-title flex justify-between">
                <h2>
                  <i :class="'fas fa-'+ (player.sexe === 'M' ? 'person':'person-dress')+' mr-2'"/>
                  {{ player.prenom }} {{ player.nom }}
                </h2>
                <div v-if="player.show_photo === 1" class="avatar">
                  <div class="w-24 rounded-full">
                    <img :src="'/'+player.path_photo" alt=""/>
                  </div>
                </div>
                <div v-if="player.show_photo === 0" class="avatar placeholder" title="ne veut pas montrer sa photo">
                  <div class="bg-neutral text-neutral-content w-24 rounded-full">
                    <span class="text-3xl">
                      {{ player.prenom[0] }}{{ player.nom[0] }}
                    </span>
                  </div>
                </div>
              </div>
              <p v-if="player.est_actif === 1" class="text-success"><i class="fas fa-check mr-2"/>
                {{ player.date_homologation }}</p>
              <p v-if="player.est_actif === 0" class="text-error"><i class="fas fa-xmark mr-2"/>non licencié ?</p>
              <p v-if="player.est_responsable_club === 1"><i class="fas fa-medal mr-2"/>Responsable du club</p>
              <p v-if="player.is_leader === 1"><i class="fas fa-medal mr-2"/>Responsable</p>
              <p v-if="player.is_vice_leader === 1"><i class="fas fa-medal mr-2"/>Suppléant</p>
              <p v-if="player.is_captain === true"><i class="fas fa-medal mr-2"/>Capitaine</p>
              <p v-if="player.email !== null"><i class="fas fa-envelope mr-2"/>{{ player.email }}</p>
              <p v-if="player.email2 !== null"><i class="fas fa-envelope mr-2"/>{{ player.email2 }}</p>
              <p v-if="player.telephone !== null"><i class="fas fa-phone mr-2"/>{{ player.telephone }}</p>
              <div class="card-actions justify-end">
                <router-link :to="'/player/' + player.id"
                             class="btn btn-primary text-xl rounded-full">
                  <i class="fas fa-edit"></i> Modifier
                </router-link>
              </div>
            </div>
          </li>
          <li>
            <button class="btn btn-success text-3xl rounded-full"><i class="fas fa-plus"/></button>
          </li>
        </ul>
      </div>
    `,
    data() {
        return {
            players: [],
        };
    },
    methods: {
        fetch() {
            axios
                .get("/rest/action.php/player/getMyPlayers")
                .then((response) => {
                    this.players = response.data;
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des joueurs :", error);
                });
        },
    },
    created() {
        this.fetch();
    },
};