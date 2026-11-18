// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — La page du match dit à l'équipe connectée ce qu'il lui reste à
 * signer (issue #486) : un responsable confondait la fiche équipes et la
 * feuille de match. La logique des étapes est dans `utils/signatureSteps.js`.
 *
 * Setup : le match de test de #240 (7 jours, rien de signé) en session de
 * vrai responsable de l'équipe à domicile (`?role=leader`).
 */
test.describe('Issue #486 — étape de signature restante', () => {
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

    test("l'équipe voit qu'il lui reste la fiche équipes à signer", async ({ page }) => {
        await page.goto(`/match.html?id_match=${setup.id_match}`);
        const step = page.getByTestId('next-step');
        await expect(step).toBeVisible({ timeout: 15000 });
        await expect(step).toContainText('Il vous reste à signer la fiche équipes.');
        await expect(step).toContainText('la feuille de match sera à signer après le match');
        await page.screenshot({ path: 'test-results/issue-486/etape-restante.png', fullPage: true });

        // Issue #488 : 3 actions, 3 boutons, tous dans la colonne de l'équipe ;
        // la colonne adverse ne montre que l'état.
        const steps = page.getByTestId('match-steps');
        await expect(steps.getByRole('button')).toHaveCount(3);
        await expect(steps.getByText('en attente', { exact: true })).toHaveCount(3);
        await expect(steps.getByText('votre équipe')).toHaveCount(1);
    });
});

test.describe('Issue #488 — un bouton par action pour la commission', () => {
    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/match_actions_teardown.php');
    });

    test('la commission a un seul bouton par étape à signer', async ({ page }) => {
        await page.goto('/e2e/helpers/match_actions_setup.php');
        const setup = JSON.parse(await page.locator('body').innerText());
        if (setup.error) throw new Error(`Setup failed: ${setup.error}`);
        await page.goto(`/match.html?id_match=${setup.id_match}`);
        const steps = page.getByTestId('match-steps');
        await expect(steps).toBeVisible({ timeout: 15000 });
        // fiche équipes + feuille de match ; pas de sondage pour la commission
        await expect(steps.getByRole('button')).toHaveCount(2);
        await expect(steps.getByText('en attente', { exact: true })).toHaveCount(6);
        await expect(page.getByTestId('next-step')).toHaveCount(0);
        await page.screenshot({ path: 'test-results/issue-486/commission.png', fullPage: true });
    });
});
