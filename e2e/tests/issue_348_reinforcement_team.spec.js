// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Un renfort renforce une équipe désignée (issue #348)
 *
 * Setup : le match de test de #240, en vrai responsable de l'équipe à domicile
 * (`?role=leader`). Les règles d'effectif, de mixité et de demi-saison sont
 * couvertes par unit_tests/ReinforcementRulesTest.php ; ici, l'écran.
 */
test.describe('Issue #348 — équipe renforcée', () => {
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

    test('un responsable ne renforce que son équipe', async ({ page }) => {
        const match = await (await page.request.get('/rest/action.php/matchmgr/get_match',
            { params: { id_match: String(setup.id_match) } })).json();

        await page.goto(`/team_sheets.html?id_match=${setup.id_match}`);
        const choix = page.getByTestId('renfort-team');
        await expect(choix).toBeVisible({ timeout: 15000 });
        await expect(choix.locator('option')).toHaveText([match.equipe_dom]);
        await expect(page.getByText('Un renfort par match et par équipe')).toBeVisible();
        await page.screenshot({ path: 'test-results/issue-348/renfort-pour.png', fullPage: true });
    });

    test("désigner l'équipe adverse est refusé par le serveur", async ({ page }) => {
        const match = await (await page.request.get('/rest/action.php/matchmgr/get_match',
            { params: { id_match: String(setup.id_match) } })).json();
        const joueurs = await (await page.request.get('/rest/action.php/matchmgr/getReinforcementPlayers',
            { params: { id_match: String(setup.id_match), query: 'a' } })).json();
        const renfort = Array.isArray(joueurs) ? joueurs.find(j => Number(j.has_photo) === 1 && !j.reinforcement_blocked) : null;
        test.skip(!renfort || !['m', 'f', 'mo'].includes(match.code_competition),
            'pas de championnat ou pas de renfort possible pour ce match de test');
        const refus = await page.request.post('/rest/action.php/matchmgr/manage_match_players', {
            form: {
                id_match: String(setup.id_match),
                'player_ids[]': String(renfort.id),
                [`reinforcements[${renfort.id}]`]: String(match.id_equipe_ext),
            },
        });
        expect(refus.status(), await refus.text()).toBe(403);
    });
});
