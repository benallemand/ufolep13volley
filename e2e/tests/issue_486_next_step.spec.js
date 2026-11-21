// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — La page du match dit à l'équipe connectée ce qu'il lui reste à faire
 * (issue #486, page unique depuis #490). La logique des étapes est dans
 * `utils/signatureSteps.js`.
 *
 * Setup : le match de test de #240 (joué, rien de saisi).
 */
test.describe('Issue #486 — étape restante', () => {
    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/match_actions_teardown.php');
    });

    test("le responsable voit l'étape restante, les suivantes sont verrouillées", async ({ page }) => {
        await page.goto('/e2e/helpers/match_actions_setup.php?role=leader');
        const setup = JSON.parse(await page.locator('body').innerText());
        if (setup.error) throw new Error(`Setup failed: ${setup.error}`);
        await page.goto(`/match.html?id_match=${setup.id_match}`);

        const step = page.getByTestId('next-step');
        await expect(step).toBeVisible({ timeout: 15000 });
        await expect(step).toContainText('Il vous reste à renseigner les présents.');
        await expect(page.getByTestId('step-presents')).toHaveAttribute('data-state', 'current');
        for (const key of ['fiche', 'score', 'feuille']) {
            await expect(page.getByTestId(`step-${key}`)).toHaveAttribute('data-state', 'locked');
        }
        await page.screenshot({ path: 'test-results/issue-486/etape-restante.png', fullPage: true });
    });

    test("la commission n'a pas d'encadré « à faire »", async ({ page }) => {
        await page.goto('/e2e/helpers/match_actions_setup.php');
        const setup = JSON.parse(await page.locator('body').innerText());
        if (setup.error) throw new Error(`Setup failed: ${setup.error}`);
        await page.goto(`/match.html?id_match=${setup.id_match}`);
        await expect(page.getByTestId('match-steps')).toBeVisible({ timeout: 15000 });
        await expect(page.getByTestId('next-step')).toHaveCount(0);
        await expect(page.getByTestId('step-presents')).toHaveAttribute('data-state', 'current');
        await page.screenshot({ path: 'test-results/issue-486/commission.png', fullPage: true });
    });
});
