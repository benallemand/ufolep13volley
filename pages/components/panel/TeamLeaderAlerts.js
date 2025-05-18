export default {
    template: `
      <div v-if="alerts.length > 0">
        <p class="text-xl">Alertes</p>
        <div class="bg-base-200 border border-2 border-base-300 p-4 flex justify-center flex-wrap gap-2">
          <div v-for="alert in alerts" :key="alert.issue" class="card w-96 shadow-xl">
            <div class="card-body">
              <h2 class="card-title">{{ alert.title }}</h2>
              <p>{{ alert.content }}</p>
            </div>
          </div>
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
                    this.alerts = response.data.map((item) => {
                        switch (item.expected_action) {
                            case 'showHelpSelectLeader':
                                item.title = "Responsable d'équipe non défini";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs, et de désigner un responsable d'équipe";
                                break;
                            case 'showHelpSelectViceLeader':
                                item.title = "Responsable d'équipe suppléant non défini";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs, et de désigner un suppléant au responsable d'équipe (cette action est optionnelle).";
                                break;
                            case 'showHelpSelectCaptain':
                                item.title = "Capitaine non défini";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs, et de désigner le capitaine de l'équipe.";
                                break;
                            case 'showHelpSelectTimeSlot':
                                item.title = "Ajout de créneau de gymnase";
                                item.content = "Merci de vous rendre dans le menu de gestion des gymnases, et d'indiquer les créneaux auxquels vous pouvez recevoir les matches.";
                                break;
                            case 'showHelpAddPhoneNumber':
                                item.title = "Numéro de téléphone";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs, éditer le responsable ou le suppléant, et ajouter au moins un numéro de téléphone.";
                                break;
                            case 'showHelpAddEmail':
                                item.title = "Adresse email";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs, éditer le responsable ou le suppléant, et ajouter au moins une adresse email.";
                                break;
                            case 'showHelpAddPlayer':
                                item.title = "Ajout de joueur";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs, cliquer sur 'Ajouter un joueur' pour sélectionner l'un des joueurs connus du système. Si ce joueur n'existe pas, cliquer sur 'Créer un joueur'. Les joueurs n'apparaissent pas immédiatement sur la fiche équipe, ils doivent être activés par les responsables UFOLEP.";
                                break;
                            case 'showHelpInactivePlayers':
                                item.title = "Joueurs inactifs";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs. Les joueurs en rouge sont inactifs. Ils n'apparaitront sur la fiche équipe qu'une fois actifs. Pour ce faire, les responsables UFOLEP doivent vérifier la validité de ces joueurs. Si le délai de prise en compte vous semble long, merci de relancer le responsable UFOLEP du championnat/division/coupe/poule concerné.";
                                break;
                            case 'showHelpPlayersWithoutLicenceNumber':
                                item.title = "Joueurs sans licence";
                                item.content = "Merci de vous rendre dans le menu de gestion des joueurs. Certains joueurs n'ont pas encore leur numéro de licence. Ils ne peuvent être vérifiés par la commission que lorsqu'ils auront leur numéro de licence. Merci de renseigner ce numéro dès que vous l'aurez récupéré.";
                                break;
                        }
                        return item
                    });
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