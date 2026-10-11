/**
 * Équipes dont le compte est responsable : celles du compte (`team_ids`,
 * users_teams) et l'équipe courante. Un responsable de plusieurs équipes voit
 * les actions d'un match de chacune, sans changer d'équipe courante (#421).
 */
export function myTeamIds(user) {
    const ids = (user?.team_ids || []).map(Number);
    if (user?.is_team_leader && user?.id_equipe) {
        ids.push(Number(user.id_equipe));
    }
    return ids;
}

export function isMyTeamInMatch(match, user) {
    const ids = myTeamIds(user);
    return ids.includes(Number(match.id_equipe_dom)) || ids.includes(Number(match.id_equipe_ext));
}

/**
 * L'équipe au nom de laquelle le compte agit sur ce match : l'équipe courante
 * si elle le joue, sinon celle du compte qui le joue ; null s'il n'en joue
 * aucune ou les deux (le serveur demande alors de choisir l'équipe). Même
 * règle que `MatchMgr::get_report_acting_team_id`.
 */
export function actingTeamId(match, user) {
    const matchTeams = [Number(match.id_equipe_dom), Number(match.id_equipe_ext)];
    const current = Number(user?.id_equipe);
    if (user?.is_team_leader && matchTeams.includes(current)) {
        return current;
    }
    const own = (user?.team_ids || []).map(Number);
    const mine = matchTeams.filter(id => own.includes(id));
    return mine.length === 1 ? mine[0] : null;
}

export function canAskReport(match, user) {
    return actingTeamId(match, user) !== null
        && match.match_status === 'CONFIRMED'
        && match.is_match_score_filled === 0
        && match.report_status === 'NOT_ASKED';
}

export function canAcceptReport(match, user) {
    const team = actingTeamId(match, user);
    return team !== null
        && ((team === Number(match.id_equipe_dom) && match.report_status === 'ASKED_BY_EXT')
            || (team === Number(match.id_equipe_ext) && match.report_status === 'ASKED_BY_DOM'))
        && match.is_match_score_filled === 0;
}

export function canRefuseReport(match, user) {
    return canAcceptReport(match, user);
}

export function canGiveReportDate(match, user) {
    const team = actingTeamId(match, user);
    return team !== null
        && ((team === Number(match.id_equipe_dom) && match.report_status === 'ACCEPTED_BY_DOM')
            || (team === Number(match.id_equipe_ext) && match.report_status === 'ACCEPTED_BY_EXT'))
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
            // un refus (403, 409) porte un message métier : le montrer
            const message = error?.response?.data?.message;
            alert(message ? "Erreur : " + message : "Une erreur est survenue...");
            if (onError) onError();
        });
}
