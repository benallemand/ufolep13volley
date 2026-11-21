// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Score verrouillé tant que les présents ne sont pas signés, sauf
 * forfait déclaré (issue #344 ; depuis #490, une signature suffit et tout est
 * sur la page de match unique).
 *
 * Setup : le match de test de #240 en session de vrai responsable de l'équipe
 * à domicile (`?role=leader`) — l'admin est exempté de la règle.
 */
test.describe('Issue #344 — saisie du score', () => {
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

    test('score verrouillé sans présents signés, forfait déclaré sans fiche', async ({ page }) => {
        await page.goto(`/match.html?id_match=${setup.id_match}`);

        // L'étape du score est verrouillée tant que personne n'a signé les présents.
        await expect(page.getByTestId('step-score')).toHaveAttribute('data-state', 'locked', { timeout: 15000 });
        await expect(page.getByTestId('step-score')).toContainText('après la fiche équipes');

        // Même un appel direct à l'API est refusé.
        const direct = await page.request.post('/rest/action.php/matchmgr/save_match', {
            form: {
                id_match: String(setup.id_match), code_match: setup.code_match,
                set_1_dom: '25', set_2_dom: '25', set_3_dom: '25', set_4_dom: '0', set_5_dom: '0',
                set_1_ext: '10', set_2_ext: '10', set_3_ext: '10', set_4_ext: '0', set_5_ext: '0',
                referee: 'HOME', note: 'E2E #344',
            },
        });
        expect(direct.status(), await direct.text()).toBe(409);
        await page.screenshot({ path: 'test-results/issue-344/score-verrouille.png', fullPage: true });

        // Forfait de l'équipe adverse (extérieur), depuis l'étape « Le match ».
        await page.getByTestId('step-match').getByRole('button').first().click();
        await page.getByTestId('forfeit')
            .getByRole('button', { name: `${setup.equipe_adverse} déclare forfait` }).click();
        const saved = page.waitForResponse(r => r.url().includes('/matchmgr/save_match'));
        await page.getByRole('button', { name: 'Confirmer le forfait' }).click();
        const reponse = await saved;
        expect(reponse.ok(), await reponse.text()).toBe(true);

        const match = await (await page.request.get('/rest/action.php/matchmgr/get_match',
            { params: { id_match: String(setup.id_match) } })).json();
        expect([match.set_1_dom, match.set_1_ext, match.set_2_dom, match.set_3_ext].map(Number)).toEqual([25, 0, 25, 0]);
        await page.getByTestId('step-score').getByRole('button').first().click();
        await expect(page.locator('input[type="number"]').first()).toHaveValue('25', { timeout: 10000 });
    });
});
