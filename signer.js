import {onError, onSuccess} from "./toaster.js";

export function genericSignMatch(controller, id_match) {
    const message = "Je confirme avoir pris connaissance du score saisi sur le site." +
        "\nEn signant numériquement la feuille de match, il n'est plus nécessaire de fournir de feuille de match au format papier." +
        "\nMerci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.";
    if (window.confirm(message)) {
        controller.isLoading = true;
        const formData = new FormData();
        formData.append('id_match', id_match);
        axios.post('/rest/action.php/matchmgr/sign_match_sheet', formData)
            .then(
                response => {
                    onSuccess(controller, response)
                    controller.reloadData();
                }
            )
            .catch(error => {
                onError(controller, error)
            });
    }
}

export function genericSignSheet(controller, id_match) {
    const message = "Je confirme avoir pris connaissance des joueurs/joueuses présent(e)s." +
        "\nLes personnes présentes pour ce match ont été déclarées présentes sur le site, sur la page de gestion du match." +
        "\nEn signant numériquement la fiche équipe, il n'est plus nécessaire de fournir de fiche équipe au format papier." +
        "\nMerci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.";
    if (window.confirm(message)) {
        controller.isLoading = true;
        const formData = new FormData();
        formData.append('id_match', id_match);
        axios.post('/rest/action.php/matchmgr/sign_team_sheet', formData)
            .then(
                response => {
                    onSuccess(controller, response)
                    controller.reloadData();
                }
            )
            .catch(error => {
                onError(controller, error)
            });
    }
}