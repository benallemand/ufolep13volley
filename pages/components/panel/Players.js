import {onError, onSuccess} from "../../../toaster.js";

export default {
    template: `
      <div>
        <div class="bg-base-200 border border-2 border-base-300 p-4 flex flex-wrap gap-1">
          <span class="label-text">Ajouter un joueur existant</span>
          <input
              type="text"
              v-model="searchTerm"
              placeholder="Rechercher un joueur..."
              @input="fetchPlayers"
              class="input input-bordered w-full"
          />
          <ul v-if="filteredPlayers.length" class="bg-white border w-full mt-1 z-10 shadow-md">
            <li
                v-for="player in filteredPlayers"
                :key="player.id"
                @click="addPlayer(player)"
                class="p-2 hover:bg-gray-200 cursor-pointer"
            >
              {{ player.full_name }}
            </li>
          </ul>
          <div class="flex gap-2">
            <router-link :to="'/player/new'"
                         class="btn btn-primary">
              <i class="fas fa-plus mr-2"></i>créer...
            </router-link>
            <button class="btn btn-secondary" @click="openImportModal">
              <i class="fas fa-upload mr-2"></i>importer depuis le pdf...
            </button>
          </div>
        </div>
        
        <!-- Modal d'import PDF -->
        <dialog ref="importModal" class="modal">
          <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">Importer les licences depuis un PDF</h3>
            <form @submit.prevent="handleImportSubmit">
              <div class="form-control w-full">
                <label class="label">
                  <span class="label-text">Fichier PDF</span>
                </label>
                <input 
                  type="file" 
                  accept="application/pdf"
                  ref="pdfFileInput"
                  @change="onPdfFileChange"
                  class="file-input file-input-bordered w-full" 
                  required
                />
              </div>
              <div class="modal-action">
                <button type="button" class="btn" @click="closeImportModal">Annuler</button>
                <button type="submit" class="btn btn-primary" :disabled="!selectedPdfFile || isUploading">
                  <span v-if="isUploading" class="loading loading-spinner"></span>
                  {{ isUploading ? 'Import en cours...' : 'Importer' }}
                </button>
              </div>
            </form>
          </div>
          <form method="dialog" class="modal-backdrop">
            <button @click="closeImportModal">close</button>
          </form>
        </dialog>
        
        <div class="bg-base-200 border border-2 border-base-300 p-4">
          <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <li v-for="player in team_players" :key="player.id" class="card shadow-md bg-base-100">
              <div class="card-title flex justify-around m-2">
                <h2>
                  <i :class="'fas fa-'+ (player.sexe === 'M' ? 'person':'person-dress')+' mr-2'"/>
                  {{ player.prenom }} {{ player.nom }}
                  <span :class="'badge ' + (player.est_actif === 1 ? 'badge-success':'badge-error')">
                  {{ String(player.departement_affiliation).padStart(3, '0') }}
                    {{ player.num_licence }}
                </span>
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
                    <i class="fas fa-check mr-2"/>licence validée le {{ player.date_homologation }}
                  </div>
                  <div v-if="player.est_actif === 0" class="text-error"><i class="fas fa-xmark mr-2"/>non licencié ?
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button
                        :class="'btn btn-xs ' + (player.is_leader === true ? 'btn-primary':'btn-outline line-through')"
                        @click="onMedalClick(player, 'is_leader')">
                      <i class="fas fa-medal"/>responsable d'équipe
                    </button>
                    <button
                        :class="'btn btn-xs ' + (player.is_vice_leader === true ? 'btn-primary':'btn-outline line-through')"
                        @click="onMedalClick(player, 'is_vice_leader')">
                      <i class="fas fa-medal"/>suppléant
                    </button>
                    <button
                        :class="'btn btn-xs ' + (player.is_captain === true ? 'btn-primary':'btn-outline line-through')"
                        @click="onMedalClick(player, 'is_captain')">
                      <i class="fas fa-medal"/>capitaine
                    </button>
                  </div>
                  <div v-if="player.email !== null"><i class="fas fa-envelope mr-2"/>{{ player.email }}</div>
                  <div v-if="player.email2 !== null"><i class="fas fa-envelope mr-2"/>{{ player.email2 }}</div>
                  <div v-if="player.telephone !== null"><i class="fas fa-phone mr-2"/>{{ player.telephone }}</div>
                </div>
              </div>
              <div class="card-actions justify-end mr-2 mb-2">
                <button class="btn btn-error"
                        @click="onRemovePlayerFromTeamClick(player)"><i class="fas fa-user-slash mr-2"></i>retirer
                </button>
                <router-link :to="'/player/' + player.id"
                             class="btn btn-primary">
                  <i class="fas fa-edit mr-2"></i>modifier
                </router-link>
              </div>
            </li>
          </ul>
        </div>
      </div>
    `,
    props: {
        fetchUrl: {
            type: String,
            required: true,
        }
    },
    data() {
        return {
            team_players: [],
            all_players: [],
            searchTerm: '',
            filteredPlayers: [],
            selectedPdfFile: null,
            isUploading: false,
        };
    },
    methods: {
        fetchPlayers() {
            if (this.searchTerm.length < 2) {
                this.filteredPlayers = [];
                return;
            }
            this.filteredPlayers = this.all_players.filter(player =>
                player.full_name.toLowerCase().includes(this.searchTerm.toLowerCase())
                && !this.team_players.map(teamPlayer => teamPlayer.id).includes(player.id)
            );
        },
        addPlayer(player) {
            const playerId = player.id;
            if (confirm("Voulez-vous vraiment ajouter ce joueur à votre équipe ?")) {
                const formData = new FormData();
                formData.append('idPlayer', playerId);
                axios
                    .post('/rest/action.php/player/addPlayerToMyTeam', formData)
                    .then(
                        response => {
                            onSuccess(this, response)
                            this.filteredPlayers = [];
                            this.searchTerm = '';
                            this.fetchTeamPlayers();
                        }
                    )
                    .catch(error => {
                        onError(this, error)
                    });
            }
        },
        fetchTeamPlayers() {
            axios
                .get(this.fetchUrl)
                .then((response) => {
                    this.team_players = response.data.map(player => ({
                        ...player,
                        isDragging: false,
                    }));
                })
                .catch((error) => {
                    console.error("Erreur lors du chargement des joueurs :", error);
                });
        },
        fetchAllPlayers() {
            axios
                .get("/rest/action.php/player/get")
                .then((response) => {
                    this.all_players = response.data;
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
        onMedalClick(player, field_name) {
            let action = '';
            switch (field_name) {
                case 'is_leader':
                    if (player.is_leader) {
                        return;
                    }
                    action = 'set_leader';
                    break;
                case 'is_vice_leader':
                    if (player.is_vice_leader) {
                        return;
                    }
                    action = 'set_vice_leader';
                    break;
                case 'is_captain':
                    if (player.is_captain) {
                        return;
                    }
                    action = 'set_captain';
                    break;
                default:
                    return;
            }
            if (action === '') {
                return;
            }
            const formData = new FormData();
            formData.append("ids[]", player.id);
            axios
                .post(`/rest/action.php/player/${action}`, formData)
                .then((response) => {
                    onSuccess(this, response)
                    this.fetchTeamPlayers();
                })
                .catch((error) => {
                    onError(this, error)
                });
        },
        onRemovePlayerFromTeamClick(player) {
            let action = 'remove_from_team';
            const formData = new FormData();
            formData.append("ids[]", player.id);
            axios
                .post(`/rest/action.php/player/${action}`, formData)
                .then((response) => {
                    onSuccess(this, response)
                    this.fetchTeamPlayers();
                })
                .catch((error) => {
                    onError(this, error)
                });
        },
        openImportModal() {
            this.$refs.importModal.showModal();
        },
        closeImportModal() {
            this.$refs.importModal.close();
            this.selectedPdfFile = null;
            if (this.$refs.pdfFileInput) {
                this.$refs.pdfFileInput.value = '';
            }
        },
        onPdfFileChange(event) {
            this.selectedPdfFile = event.target.files[0];
        },
        handleImportSubmit() {
            if (!this.selectedPdfFile) {
                alert("Veuillez sélectionner un fichier PDF");
                return;
            }
            
            this.isUploading = true;
            const formData = new FormData();
            formData.append('licences', this.selectedPdfFile);
            
            axios
                .post('/rest/action.php/player/update_from_licence_file', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                })
                .then((response) => {
                    onSuccess(this, response);
                    this.closeImportModal();
                    this.fetchTeamPlayers();
                    this.fetchAllPlayers();
                })
                .catch((error) => {
                    onError(this, error);
                })
                .finally(() => {
                    this.isUploading = false;
                });
        },
        isValidFile(file) {
            const allowedTypes = ["image/jpeg", "image/png", "image/gif"];
            return allowedTypes.includes(file.type);
        },
        uploadPhoto(file, player) {
            if (!file || !this.isValidFile(file)) {
                alert("Fichier invalide. Veuillez déposer une image.");
                return;
            }
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
                    onSuccess(this, response)
                    this.fetchTeamPlayers();
                })
                .catch((error) => {
                    onError(this, error)
                });
        }
    },
    created() {
        this.fetchTeamPlayers();
        this.fetchAllPlayers();
    },
};
