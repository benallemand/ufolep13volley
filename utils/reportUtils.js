export function canAskReport(match, user, isLeader) {
    return isLeader
        && [match.id_equipe_dom, match.id_equipe_ext].includes(user?.id_equipe)
        && match.is_match_score_filled === 0
        && match.report_status === 'NOT_ASKED';
}

export function canAcceptReport(match, user, isLeader) {
    return isLeader
        && ((match.id_equipe_dom === user?.id_equipe && match.report_status === 'ASKED_BY_EXT')
            || (match.id_equipe_ext === user?.id_equipe && match.report_status === 'ASKED_BY_DOM'))
        && match.is_match_score_filled === 0;
}

export function canRefuseReport(match, user, isLeader) {
    return isLeader
        && ((match.id_equipe_dom === user?.id_equipe && match.report_status === 'ASKED_BY_EXT')
            || (match.id_equipe_ext === user?.id_equipe && match.report_status === 'ASKED_BY_DOM'))
        && match.is_match_score_filled === 0;
}

export function canGiveReportDate(match, user, isLeader) {
    return isLeader
        && ((match.id_equipe_dom === user?.id_equipe && match.report_status === 'ACCEPTED_BY_DOM')
            || (match.id_equipe_ext === user?.id_equipe && match.report_status === 'ACCEPTED_BY_EXT'))
        && match.is_match_score_filled === 0;
}

export function postReportAction(axios, codeMatch, actionName, onSuccess, onError) {
    const params = new FormData();
    params.append('code_match', codeMatch);
    if (['askForReport', 'refuseReport'].includes(actionName)) {
        const reason = prompt("Veuillez saisir la raison:");
        if (reason === null) {
            return;
        }
        if (reason.trim() === "") {
            alert("La raison ne peut pas être vide.");
            return;
        }
        params.append('reason', reason);
    }
    if (['giveReportDate'].includes(actionName)) {
        const newDate = prompt("Veuillez saisir la nouvelle date au format JJ/MM/AAAA:");
        if (newDate === null) {
            return;
        }
        if (newDate.trim() === "") {
            alert("La date ne peut pas être vide.");
            return;
        }
        params.append('report_date', newDate);
    }
    axios.post(`/rest/action.php/matchmgr/${actionName}`, params)
        .then(response => {
            if (response.data.success) {
                alert("Envoyé avec succès.");
                if (onSuccess) onSuccess();
            } else {
                alert("Erreur lors de l'envoi: " + response.data.message);
                if (onError) onError();
            }
        })
        .catch(error => {
            console.error("Erreur:", error);
            alert("Une erreur est survenue...");
            if (onError) onError();
        });
}
