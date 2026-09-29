// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Sondage fair-play en -- - = + ++, prérempli à = (issue #350)
 *
 * Setup : le match de test de #240 (session admin, match joué sans données).
 * Le sondage créé est supprimé avec le match au teardown (FK ON DELETE CASCADE).
 */
test.describe('Issue #350 — échelle du sondage', () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        await page.goto('/e2e/helpers/match_actions_setup.php');
        const body = JSON.parse(await page.locator('body').innerText());
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        setup = body;
    });

    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/match_actions_teardown.php');
        setup = null;
    });

    test('légende, préremplissage à =, commentaire exigé pour --', async ({ page }) => {
        await page.goto(`/survey.html?id_match=${setup.id_match}`);

        // La légende explique les cinq notes.
        const legende = page.getByTestId('survey-legend');
        await expect(legende).toBeVisible({ timeout: 15000 });
        await expect(legende.locator('li .badge')).toHaveText(['--', '-', '=', '+', '++']);

        // Chaque critère est prérempli à =.
        const criteres = ['Ponctualité', "Etat d'esprit", 'Arbitrage', 'Apéro', 'Global'];
        for (const critere of criteres) {
            const groupe = page.getByRole('radiogroup', { name: critere });
            await expect(groupe.getByRole('radio', { name: '=', exact: true })).toBeChecked();
        }
        await page.screenshot({ path: 'test-results/issue-350/sondage-neuf.png', fullPage: true });

        // Une note -- sans commentaire est refusée.
        const global = page.getByRole('radiogroup', { name: 'Global' });
        await global.getByRole('radio', { name: '--', exact: true }).check();
        await expect(page.getByText('(obligatoire : une note est à --)')).toBeVisible();
        // Le champ devient obligatoire : le navigateur bloque l'envoi lui-même.
        const commentaire = page.getByPlaceholder('Ajouter un commentaire');
        let envois = 0;
        page.on('request', r => { if (r.url().includes('/matchmgr/save_survey')) envois++; });
        await page.getByRole('button', { name: 'Enregistrer' }).click();
        expect(await commentaire.evaluate(el => el.validity.valueMissing)).toBe(true);
        await page.waitForTimeout(500);
        expect(envois, 'aucun enregistrement sans commentaire').toBe(0);

        // Avec un commentaire, le sondage s'enregistre sur la nouvelle échelle.
        await page.getByPlaceholder('Ajouter un commentaire').fill('E2E #350 : retard non prévenu');
        await page.getByRole('radiogroup', { name: 'Ponctualité' })
            .getByRole('radio', { name: '+', exact: true }).check();
        const saved = page.waitForResponse(r => r.url().includes('/matchmgr/save_survey'));
        await page.getByRole('button', { name: 'Enregistrer' }).click();
        const reponse = await saved;
        expect(reponse.ok(), await reponse.text()).toBe(true);
        await page.screenshot({ path: 'test-results/issue-350/sondage-enregistre.png', fullPage: true });

        // Ce que le formulaire envoie. La relecture n'est pas testée ici : la
        // session du helper est un admin, membre d'aucune des deux équipes, et
        // un sondage ne se relit que par l'équipe sondeuse (users_teams). Le
        // stockage est couvert par unit_tests/SurveyTest.php.
        const envoi = reponse.request().postData() || '';
        const champ = (nom) => (envoi.match(new RegExp(`name="${nom}"\\r?\\n\\r?\\n(-?\\d+)`)) || [])[1];
        expect(champ('global')).toBe('-2');
        expect(champ('on_time')).toBe('1');
        expect(champ('spirit')).toBe('0');
        expect(champ('referee')).toBe('0');
    });
});
