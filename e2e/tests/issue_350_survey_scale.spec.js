// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Sondage fair-play en -- - = + ++, prérempli à = (issue #350)
 *
 * Depuis #490, le sondage est la dernière étape de la page de match. Setup :
 * e2e/helpers/match_flow_setup.php, match joué (présents signés, score saisi),
 * en session du responsable de l'équipe à domicile.
 */
const HELPER = '/e2e/helpers/match_flow_setup.php';

test.describe('Issue #350 — échelle du sondage', () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        await page.goto(HELPER);
        setup = JSON.parse(await page.locator('body').innerText());
        if (setup.error) throw new Error(`Setup failed: ${setup.error}`);
        const played = await page.request.get(`${HELPER}?action=played`);
        expect(played.ok(), await played.text()).toBe(true);
    });

    test.afterEach(async ({ page }) => {
        await page.request.get(`${HELPER}?action=teardown`);
        setup = null;
    });

    test('légende, préremplissage à =, commentaire exigé pour --', async ({ page }) => {
        await page.goto(`/match.html?id_match=${setup.id_match}#sondage`);

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
        await page.getByRole('button', { name: 'Envoyer le sondage' }).click();
        expect(await commentaire.evaluate(el => el.validity.valueMissing)).toBe(true);
        await page.waitForTimeout(500);
        expect(envois, 'aucun enregistrement sans commentaire').toBe(0);

        // Avec un commentaire, le sondage s'enregistre sur la nouvelle échelle.
        await commentaire.fill('E2E #350 : retard non prévenu');
        await page.getByRole('radiogroup', { name: 'Ponctualité' })
            .getByRole('radio', { name: '+', exact: true }).check();
        const saved = page.waitForResponse(r => r.url().includes('/matchmgr/save_survey'));
        await page.getByRole('button', { name: 'Envoyer le sondage' }).click();
        const reponse = await saved;
        expect(reponse.ok(), await reponse.text()).toBe(true);
        await page.screenshot({ path: 'test-results/issue-350/sondage-enregistre.png', fullPage: true });

        const envoi = reponse.request().postData() || '';
        const champ = (nom) => (envoi.match(new RegExp(`name="${nom}"\\r?\\n\\r?\\n(-?\\d+)`)) || [])[1];
        expect(champ('global')).toBe('-2');
        expect(champ('on_time')).toBe('1');
        expect(champ('spirit')).toBe('0');
        expect(champ('referee')).toBe('0');

        // Le sondage se relit : l'étape le montre envoyé, notes comprises.
        await page.reload();
        await expect(page.getByTestId('survey-step')).toContainText('Sondage envoyé', { timeout: 15000 });
        await expect(page.getByRole('radiogroup', { name: 'Global' }).getByRole('radio', { name: '--', exact: true })).toBeChecked();
    });
});
