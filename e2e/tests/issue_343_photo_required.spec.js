// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Pas de photo, pas de match (issue #343)
 *
 * Setup : le match de test de #240, en vrai responsable de l'équipe à domicile
 * (`?role=leader`) — l'admin, qui corrige les fiches, n'est pas bloqué.
 * Les joueurs viennent de la base : le test cherche un joueur sans photo dans
 * l'équipe et saute s'il n'y en a aucun.
 */
test.describe('Issue #343 — photo obligatoire', () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        await page.goto('/e2e/helpers/match_actions_setup.php?role=leader');
        const body = JSON.parse(await page.locator('body').innerText());
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        setup = body;
    });

    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/match_actions_teardown.php');
        setup = null;
    });

    test('un joueur sans photo est signalé, non ajoutable, et refusé par le serveur', async ({ page }) => {
        const joueurs = await (await page.request.get('/rest/action.php/matchmgr/getNotMatchPlayers',
            { params: { id_match: String(setup.id_match) } })).json();
        expect(Array.isArray(joueurs)).toBe(true);
        expect(joueurs.every(j => 'has_photo' in j), 'chaque joueur porte has_photo').toBe(true);
        const sansPhoto = joueurs.find(j => Number(j.has_photo) === 0);
        test.skip(!sansPhoto, "aucun joueur sans photo dans l'équipe de test");

        await page.goto(`/team_sheets.html?id_match=${setup.id_match}`);
        const ligne = page.locator('div.flex.items-center.mb-2')
            .filter({ hasText: `${sansPhoto.prenom} ${sansPhoto.nom}` }).first();
        await expect(ligne.getByTestId('missing-photo')).toContainText('ne peut pas jouer', { timeout: 15000 });
        await expect(ligne.getByRole('button')).toBeDisabled();
        await page.screenshot({ path: 'test-results/issue-343/photo-manquante.png', fullPage: true });

        const refus = await page.request.post('/rest/action.php/matchmgr/manage_match_players', {
            form: { id_match: String(setup.id_match), 'player_ids[]': String(sansPhoto.id) },
        });
        expect(refus.status(), await refus.text()).toBe(409);
        expect(JSON.parse(await refus.text()).message).toContain('Photo manquante');
    });
});
