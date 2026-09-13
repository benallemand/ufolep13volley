// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Membre non jouant d'une équipe (issue #325)
 *
 * Le drapeau `joueur_equipe.est_jouant` est vérifié par PHPUnit
 * (`unit_tests/NonPlayingMemberTest.php`), qui couvre les requêtes d'effectif
 * et de licence. Ce qui reste à vérifier ici, c'est ce que PHPUnit ne voit
 * pas : les deux points d'entrée par lesquels un administrateur pose le
 * drapeau, et la ligne du tiroir qui le donne à lire.
 *
 * Les deux tests sont **pilotés par la donnée** : sans membre non jouant en
 * base, ils se sautent au lieu d'échouer — ils se rallumeront d'eux-mêmes le
 * jour où le jeu en contiendra un (même parti pris que les tests de coupe,
 * issue #319).
 */

/** Voir `issue_308_detail_drawer.spec.js` : cookie posé avant toute navigation. */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    expect(session_id).toBeTruthy();
    await page.context().addCookies([{
        name: 'PHPSESSID',
        value: session_id,
        url: baseURL || 'http://localhost',
    }]);
}

test.describe('Issue #325 — membre non jouant', () => {

    /**
     * Le sélecteur « Nommer responsable » porte la case qui crée
     * l'appartenance non jouante : c'est là que se règle le cas d'usage
     * principal — l'homme qui pilote une équipe féminine.
     */
    test('le sélecteur « Nommer responsable » propose de ne pas faire jouer', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/teams');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });

        // Le bouton n'est actif qu'avec une ligne sélectionnée : on coche la
        // case de la première, sans ouvrir son tiroir.
        await rows.first().locator('input[type="checkbox"]').check();
        await page.getByRole('button', { name: /Nommer responsable/ }).click();

        const modal = page.locator('dialog.modal-open');
        await expect(modal).toBeVisible({ timeout: 15000 });
        await expect(modal.getByText('Ne joue pas dans cette équipe')).toBeVisible();

        // On referme sans rien nommer : ce test ne modifie pas la base.
        await modal.getByRole('button', { name: 'Annuler' }).click();
        await expect(modal).toHaveCount(0);
    });

    /**
     * Le tiroir des joueurs donne à lire le drapeau, sans retirer l'équipe des
     * listes existantes — une appartenance reste une appartenance.
     */
    test('le tiroir montre les équipes où la personne ne joue pas', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);

        const reponse = await request.get('/rest/action.php/player/getPlayers');
        expect(reponse.ok(), 'getPlayers doit répondre').toBeTruthy();
        const joueurs = await reponse.json();
        const nonJouant = (Array.isArray(joueurs) ? joueurs : [])
            .find((j) => String(j.non_playing_teams_list || '').trim() !== '');

        test.skip(
            nonJouant === undefined,
            "aucun membre non jouant dans ce jeu de données : rien à vérifier"
        );

        await page.goto('/admin/index.html#/players');
        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 60000 });

        await page.getByPlaceholder(/Rechercher/).first().fill(String(nonJouant.nom));
        await expect(rows.first()).toBeVisible({ timeout: 15000 });
        await rows.first().click();

        const drawer = page.getByRole('dialog');
        await expect(drawer).toBeVisible({ timeout: 15000 });
        await expect(drawer.getByText('Sans y jouer')).toBeVisible();

        // La première équipe non jouante figure AUSSI dans les équipes
        // actives : la colonne ajoutée ne retire rien aux listes existantes.
        const equipe = String(nonJouant.non_playing_teams_list).split(/<br\s*\/?>/i)[0].trim();
        expect(String(nonJouant.active_teams_list || '')).toContain(equipe);
    });
});
