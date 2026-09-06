// Issue #240 — Affiche un toast par match déjà joué ayant une action en attente
// pour le responsable d'équipe connecté (présents, signatures, score, sondage).
// Appelé après le montage de l'app sur la home publique ET l'espace responsable.
//
// La home est majoritairement consultée sans être connecté : on vérifie la
// session avant d'appeler l'endpoint, qui exige une authentification (#270).
// Sans ce court-circuit, chaque visite anonyme déclenche un appel voué au 403.
export async function showMatchActionToasts() {
    try {
        const session = await window.axios.get('/session_user.php');
        if (!session.data || session.data.error) {
            return;
        }
        const response = await window.axios.get('/rest/action.php/matchmgr/getMyPendingMatchActions');
        const items = response.data;
        if (!Array.isArray(items) || items.length === 0) {
            return;
        }
        items.forEach((item) => {
            window.Toastify({
                text: `📋 ${item.equipe_adverse} (D${item.division}) — ${item.label}`,
                duration: -1,          // reste affiché jusqu'à fermeture/clic
                close: true,
                gravity: 'top',
                position: 'right',
                stopOnFocus: true,
                style: { background: '#2563eb', cursor: 'pointer' },
                onClick: () => {
                    window.location.href = item.url;
                },
            }).showToast();
        });
    } catch (e) {
        // non connecté ou erreur réseau : pas de notification
    }
}
