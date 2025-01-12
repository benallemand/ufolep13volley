export default {
    template: `
      <div class="bg-base-200 border border-2 border-base-300 p-4">
        <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <li v-for="player in players" :key="player.id" class="card shadow-md bg-base-100">
              <div class="card-title flex justify-around m-2">
                <h2>
                  <i :class="'fas fa-'+ (player.sexe === 'M' ? 'person':'person-dress')+' mr-2'"/>
                  {{ player.prenom }} {{ player.nom }}
                </h2>
                <div
                    class="avatar relative"
                    @dragover.prevent="onDragOver(player)"
                    @dragleave="onDragLeave(player)"
                    @drop.prevent="onDrop($event, player)"
                    title="Glissez une photo ici pour mettre à jour"
                >
                  <div class="w-24 rounded-full relative">
                    <img :src="'/'+player.path_photo" alt=""/>
                    <div
                        class="absolute inset-0 rounded-full bg-black bg-opacity-50 text-white transition-opacity flex items-center justify-center text-center"
                        :class="{'opacity-100': player.isDragging, 'opacity-0 hover:opacity-100': !player.isDragging}"
                    >
                      <span>Déposez ici</span>
                    </div>
                  </div>
                </div>
              </div>
            <div class="card-body">
              <div class="flex flex-col gap-2">
                  <div>
                    <i class="fas fa-image mr-2" title="photo"/><input type="file"
                                                         class="file-input file-input-xs file-input-primary"
                                                         @change="onFileChange($event, player)"/>
                  </div>
                  <div v-if="player.est_actif === 1" class="text-success">
                    <i class="fas fa-check mr-2" title="homologué le"/>{{ player.date_homologation }}
                  </div>
                  <div v-if="player.est_actif === 0" class="text-error"><i class="fas fa-xmark mr-2"/>non licencié ?</div>
                  <div v-if="player.est_responsable_club === 1"><i class="fas fa-medal mr-2"/>responsable du club</div>
                  <div v-if="player.is_leader === true"><i class="fas fa-medal mr-2"/>responsable d'équipe</div>
                  <div v-if="player.is_vice_leader === true"><i class="fas fa-medal mr-2"/>suppléant</div>
                  <div v-if="player.is_captain === true"><i class="fas fa-medal mr-2"/>capitaine</div>
                  <div v-if="player.email !== null"><i class="fas fa-envelope mr-2"/>{{ player.email }}</div>
                  <div v-if="player.email2 !== null"><i class="fas fa-envelope mr-2"/>{{ player.email2 }}</div>
                  <div v-if="player.telephone !== null"><i class="fas fa-phone mr-2"/>{{ player.telephone }}</div>
              </div>
            </div>
            <div class="card-actions justify-end m-2">
              <router-link :to="'/player/' + player.id"
                           class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifier
              </router-link>
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
                    this.players = response.data.map(player => ({
                        ...player,
                        isDragging: false,
                    }));
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des joueurs :", error);
                });
        },
        onDragOver(player) {
            player.isDragging = true;
        },
        onDragLeave(player) {
            player.isDragging = false;
        },
        onDrop(event, player) {
            player.isDragging = false;
            const file = event.dataTransfer.files[0];
            this.uploadPhoto(file, player);
        },
        onFileChange(event, player) {
            const file = event.target.files[0];
            this.uploadPhoto(file, player);
        },
        isValidFile(file) {
            const allowedTypes = ["image/jpeg", "image/png", "image/gif"];
            return allowedTypes.includes(file.type);
        },
        uploadPhoto(file, player) {
            const notyf = new Notyf();
            if (file && this.isValidFile(file)) {
                const formData = new FormData();
                formData.append("id", player.id);
                formData.append("nom", player.nom);
                formData.append("prenom", player.prenom);
                formData.append("photo", file);

                axios
                    .post("/rest/action.php/player/uploadPhoto", formData, {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    })
                    .then((response) => {
                        notyf.success("Photo mise à jour avec succès !");
                        this.fetch();
                    })
                    .catch((error) => {
                        console.error("Erreur lors de l'upload de la photo :", error);
                        notyf.error("Échec de l'upload de la photo.");
                    });
            } else {
                notyf.error("Fichier invalide. Veuillez déposer une image.");
            }
        }
    },
    created() {
        this.fetch();
    },
};
